<?php

namespace App\Services\CompraItem;

use App\Models\Compra;
use App\Models\CompraItem;
use App\Models\Item;
use App\Services\PaginateService;
use Exception;
use Illuminate\Support\Facades\DB;

class CompraItemService
{
    public function __construct()
    {
    }

    // =========================================================
    // LOOKUPS
    // =========================================================

    public function handleLookupsCompraItem(): array
    {
        return [
            'compras' => Compra::whereNull('deleted_at')
                ->orderByDesc('data_compra')
                ->orderByDesc('id')
                ->get(['id', 'data_compra', 'numero_pedido', 'valor_total']),
            'itens' => Item::whereNull('deleted_at')
                ->where('ativo', true)
                ->orderBy('descricao')
                ->get(['id', 'descricao', 'codigo', 'unidade_medida', 'id_categoria_item']),
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
            $this->validateValores($atributes);

            $payload = $this->preparePayload($atributes);

            $newData = new CompraItem($payload);
            $saved   = $newData->save();

            if (!$saved) {
                throw new Exception('Não foi possível cadastrar Item da Compra', 500);
            }

            return (object) [
                'data'    => $newData,
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
            $record = CompraItem::where('id', $atributes->id)->first();

            if (!$record) {
                throw new Exception('Item da Compra não encontrado', 404);
            }

            $this->validateCompra((int) $atributes->id_compra);
            $this->validateItem((int) $atributes->id_item);
            $this->validateValores($atributes);

            $payload = $this->preparePayload($atributes);

            $record->fill($payload);
            $saved = $record->save();

            if (!$saved) {
                throw new Exception('Não foi possível editar Item da Compra', 500);
            }

            return (object) [
                'data'    => [],
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
            $record = CompraItem::where('id', $id)->first();

            if (!$record) {
                throw new Exception('Item da Compra não encontrado', 404);
            }

            $saved = $record->delete();

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
            'ent.qtd',
            'ent.valor_unitario',
            'ent.valor_total',
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
                    'cat.descricao as categoria_item_descricao',
                    'ent.qtd',
                    'ent.valor_unitario',
                    'ent.valor_total',
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
                'ent.qtd',
                'ent.valor_total',
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
        $exists = Compra::where('id', $idCompra)
            ->whereNull('deleted_at')
            ->exists();

        if (!$exists) {
            throw new Exception('Compra não encontrada', 422);
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

    private function validateValores(object $atributes): void
    {
        $qtd           = (float) $atributes->qtd;
        $valorUnitario = (float) $atributes->valor_unitario;
        $valorTotal    = (float) $atributes->valor_total;

        if ($qtd <= 0) {
            throw new Exception('A quantidade deve ser maior que zero', 422);
        }

        if ($valorUnitario < 0) {
            throw new Exception('O valor unitário não pode ser negativo', 422);
        }

        $valorCalculado = round($qtd * $valorUnitario, 2);

        if (round($valorTotal, 2) !== $valorCalculado) {
            throw new Exception('O valor total deve ser igual à quantidade multiplicada pelo valor unitário', 422);
        }
    }

    private function preparePayload(object $atributes): array
    {
        $qtd           = (float) $atributes->qtd;
        $valorUnitario = (float) $atributes->valor_unitario;

        return [
            'id_compra'      => (int) $atributes->id_compra,
            'id_item'        => (int) $atributes->id_item,
            'qtd'            => $qtd,
            'valor_unitario' => $valorUnitario,
            'valor_total'    => round($qtd * $valorUnitario, 2),
        ];
    }
}
