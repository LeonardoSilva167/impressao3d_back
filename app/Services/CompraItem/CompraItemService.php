<?php

namespace App\Services\CompraItem;

use App\Models\Compra;
use App\Models\CompraItem;
use App\Models\Item;
use App\Repositories\CompraItem\CompraItemRepository;
use App\Services\PaginateService;
use Exception;
use Illuminate\Support\Facades\DB;

class CompraItemService
{
    /**
     * @var CompraItemRepository $_repository
     */
    private CompraItemRepository $_repository;

    /**
     * @var CompraItemEstoqueService $_estoqueService
     */
    private CompraItemEstoqueService $_estoqueService;

    public function __construct()
    {
        $this->_repository     = new CompraItemRepository();
        $this->_estoqueService = new CompraItemEstoqueService();
    }

    // =========================================================
    // LOOKUPS
    // =========================================================

    public function handleLookupsCompraItem(): array
    {
        return [
            'compras' => Compra::whereNull('deleted_at')
                ->where('status', Compra::STATUS_ATIVA)
                ->orderByDesc('data_compra')
                ->orderByDesc('id')
                ->get(['id', 'data_compra', 'numero_pedido', 'valor_total']),
        ];
    }

    // =========================================================
    // HANDLE FUNCTIONS (orquestração + transação)
    // =========================================================

    public function handleAddCompraItem(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result             = (object) [];
            $result->compraItem = $this->createCompraItem($atributes);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleEditCompraItem(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result             = (object) [];
            $result->compraItem = $this->updateCompraItem($atributes);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleDeleteCompraItem(int|string $id): object
    {
        try {
            DB::beginTransaction();

            $result             = (object) [];
            $result->compraItem = $this->deleteCompraItem($id);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    // =========================================================
    // CRUD FUNCTIONS
    // =========================================================

    public function createCompraItem(object $atributes): object
    {
        try {
            $this->validateCompra((int) $atributes->id_compra);
            $this->validateItem((int) $atributes->id_item);

            $payload = $this->preparePayload($atributes);
            $newData = $this->_repository->create($payload);

            $this->_estoqueService->aplicarMovimentacao($newData, true);

            return (object) [
                'data'    => $newData->fresh(),
                'status'  => true,
                'message' => 'Item da Compra cadastrado com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function updateCompraItem(object $atributes): object
    {
        try {
            $record = $this->_repository->findById($atributes->id);

            if (!$record) {
                throw new Exception('Item da Compra não encontrado', 404);
            }

            $this->validateCompra((int) $atributes->id_compra);
            $this->validateItem((int) $atributes->id_item);

            $this->_estoqueService->reverterMovimentacao($record);

            $payload = $this->preparePayload($atributes);
            $saved   = $this->_repository->update($record, $payload);

            if (!$saved) {
                throw new Exception('Não foi possível editar Item da Compra', 500);
            }

            $record->refresh();
            $this->_estoqueService->aplicarMovimentacao($record);

            return (object) [
                'data'    => $record->fresh(),
                'status'  => true,
                'message' => 'Item da Compra alterado com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function deleteCompraItem(int|string $id): object
    {
        try {
            $record = $this->_repository->findById($id);

            if (!$record) {
                throw new Exception('Item da Compra não encontrado', 404);
            }

            $this->_estoqueService->reverterMovimentacao($record);

            $saved = $this->_repository->delete($record);

            if (!$saved) {
                throw new Exception('Não foi possível excluir Item da Compra', 500);
            }

            return (object) [
                'data'    => [],
                'status'  => true,
                'message' => 'Item da Compra excluído com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function createItemForCompra(object $attributes, int $idCompra): CompraItem
    {
        $this->validateItem((int) $attributes->id_item);

        $attributesWithCompra = (object) array_merge((array) $attributes, ['id_compra' => $idCompra]);
        $payload              = $this->preparePayload($attributesWithCompra);
        $newData              = $this->_repository->create($payload);

        $this->_estoqueService->aplicarMovimentacao($newData, true);

        return $newData;
    }

    public function removeAllByCompraId(int|string $idCompra): void
    {
        $itens = $this->_repository->findByCompraId($idCompra);

        foreach ($itens as $compraItem) {
            $this->_estoqueService->reverterMovimentacao($compraItem);
            $this->_repository->delete($compraItem);
        }
    }

    // =========================================================
    // QUERIES
    // =========================================================

    public function getCompraItemPaginate(object $atributes): array
    {
        $query = DB::query();

        $query->select(
            'ent.id',
            'ent.id_compra',
            'comp.data_compra',
            'comp.numero_pedido',
            'ent.id_item',
            'item.descricao as item_descricao',
            'item.codigo as item_codigo',
            'item.unidade_medida as item_unidade_medida',
            'cat.descricao as categoria_item_descricao',
            'ent.qtd_compra',
            'ent.qtd_interna',
            'ent.qtd_original',
            'ent.qtd_atual',
            'ent.gramatura_filamento',
            'ent.valor_unitario_compra',
            'ent.valor_total',
            'ent.valor_unitario_real',
            'ent.created_at',
        );

        $query->from('compras_itens as ent');
        $query->join('compras as comp', 'comp.id', '=', 'ent.id_compra');
        $query->join('itens as item', 'item.id', '=', 'ent.id_item');
        $query->join('categorias_itens as cat', 'cat.id', '=', 'item.id_categoria_item');
        $query->whereNull('ent.deleted_at');
        $query->whereNull('comp.deleted_at');
        $query->whereNull('item.deleted_at');
        $query->whereNull('cat.deleted_at');
        $query->orderByDesc('comp.data_compra');
        $query->orderBy('item.descricao');

        if (!empty($atributes->id_compra)) {
            $query->where('ent.id_compra', $atributes->id_compra);
        }

        if (!empty($atributes->id_item)) {
            $query->where('ent.id_item', $atributes->id_item);
        }

        if (!empty($atributes->id_categoria_item)) {
            $query->where('item.id_categoria_item', $atributes->id_categoria_item);
        }

        if (!empty($atributes->palavra_chave)) {
            $chave = $atributes->palavra_chave;
            $query->where(function ($q) use ($chave) {
                $q->where('item.descricao', 'like', '%' . $chave . '%')
                    ->orWhere('item.codigo', 'like', '%' . $chave . '%')
                    ->orWhere('comp.numero_pedido', 'like', '%' . $chave . '%');
            });
        }

        $paginate  = new PaginateService();
        $resultado = $paginate->_paginate(
            $query,
            $atributes->page,
            $atributes->perPage,
            ['path' => $atributes->url, 'query' => $atributes->query]
        );
        $resultado->appends((array) $atributes);

        return collect($resultado)->toArray();
    }

    public function getCompraItemId(int|string $id): array
    {
        try {
            $query = DB::table('compras_itens as ent')
                ->join('compras as comp', 'comp.id', '=', 'ent.id_compra')
                ->join('itens as item', 'item.id', '=', 'ent.id_item')
                ->join('categorias_itens as cat', 'cat.id', '=', 'item.id_categoria_item')
                ->select(
                    'ent.id',
                    'ent.id_compra',
                    'comp.data_compra',
                    'comp.numero_pedido',
                    'ent.id_item',
                    'item.descricao as item_descricao',
                    'item.codigo as item_codigo',
                    'item.unidade_medida as item_unidade_medida',
                    'item.id_categoria_item',
                    'item.estoque_atual as item_estoque_atual',
                    'item.preco_medio_atual as item_preco_medio_atual',
                    'cat.descricao as categoria_item_descricao',
                    'ent.qtd_compra',
                    'ent.qtd_interna',
                    'ent.qtd_original',
                    'ent.qtd_atual',
                    'ent.gramatura_filamento',
                    'ent.valor_unitario_compra',
                    'ent.valor_total',
                    'ent.valor_unitario_real',
                    'ent.created_at',
                )
                ->whereNull('ent.deleted_at')
                ->whereNull('comp.deleted_at')
                ->whereNull('item.deleted_at')
                ->whereNull('cat.deleted_at')
                ->where('ent.id', $id);

            $record = $query->first();

            if (!$record) {
                throw new Exception('Item da Compra não encontrado', 404);
            }

            return collect($record)->toArray();
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function getCompraItemAsync(object $params): array
    {
        $query = DB::table('compras_itens as ent')
            ->join('compras as comp', 'comp.id', '=', 'ent.id_compra')
            ->join('itens as item', 'item.id', '=', 'ent.id_item')
            ->whereNull('ent.deleted_at')
            ->whereNull('comp.deleted_at')
            ->whereNull('item.deleted_at')
            ->select(
                'ent.id',
                'ent.id_compra',
                'ent.id_item',
                'item.descricao as item_descricao',
                'item.codigo as item_codigo',
                'ent.qtd_compra',
                'ent.qtd_interna',
                'ent.qtd_original',
                'ent.qtd_atual',
                'ent.gramatura_filamento',
                'ent.valor_total',
                'ent.valor_unitario_real',
            )
            ->orderByDesc('comp.data_compra')
            ->orderBy('item.descricao');

        if (!empty($params->id_compra)) {
            $query->where('ent.id_compra', $params->id_compra);
        }

        if (!empty($params->palavra_chave)) {
            $chave = $params->palavra_chave;
            $query->where(function ($q) use ($chave) {
                $q->where('item.descricao', 'like', '%' . $chave . '%')
                    ->orWhere('item.codigo', 'like', '%' . $chave . '%');
            });
            $query->limit(10);
        }

        return $query->get()->toArray();
    }

    // =========================================================
    // HELPERS
    // =========================================================

    private function validateCompra(int $idCompra): void
    {
        $compra = Compra::where('id', $idCompra)
            ->whereNull('deleted_at')
            ->first();

        if (!$compra) {
            throw new Exception('Compra não encontrada', 422);
        }

        if ($compra->status === Compra::STATUS_CANCELADA) {
            throw new Exception('Não é possível alterar itens de uma compra cancelada.', 422);
        }
    }

    private function validateItem(int $idItem): void
    {
        $exists = Item::where('id', $idItem)
            ->whereNull('deleted_at')
            ->exists();

        if (!$exists) {
            throw new Exception('Item não encontrado', 422);
        }
    }

    private function preparePayload(object $atributes): array
    {
        if (!isset($atributes->qtd_interna)) {
            throw new Exception('A quantidade interna é obrigatória.', 422);
        }

        $qtdCompra           = (float) $atributes->qtd_compra;
        $valorUnitarioCompra = (float) $atributes->valor_unitario_compra;
        $qtdInterna          = (float) $atributes->qtd_interna;
        $gramaturaFilamento  = isset($atributes->gramatura_filamento) && $atributes->gramatura_filamento !== null
            ? (int) $atributes->gramatura_filamento
            : null;

        if ($gramaturaFilamento !== null && !in_array($gramaturaFilamento, [500, 1000], true)) {
            throw new Exception('A gramatura do filamento deve ser 500 ou 1000.', 422);
        }

        $valorTotal        = round($qtdCompra * $valorUnitarioCompra, 2);
        $valorUnitarioReal = round($valorTotal / $qtdInterna, 4);

        return [
            'id_compra'             => (int) $atributes->id_compra,
            'id_item'               => (int) $atributes->id_item,
            'qtd_compra'            => $qtdCompra,
            'qtd_interna'           => $qtdInterna,
            'qtd_original'          => $qtdInterna,
            'qtd_atual'             => $qtdInterna,
            'valor_unitario_compra' => $valorUnitarioCompra,
            'valor_total'           => $valorTotal,
            'valor_unitario_real'   => $valorUnitarioReal,
            'gramatura_filamento'   => $gramaturaFilamento,
        ];
    }
}
