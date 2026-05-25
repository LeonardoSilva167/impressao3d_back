<?php

namespace App\Services\Compra;

use App\Models\Compra;
use App\Models\PlataformaCompra;
use App\Repositories\Compra\CompraRepository;
use App\Services\CompraItem\CompraItemEstoqueService;
use App\Services\CompraItem\CompraItemService;
use App\Services\PaginateService;
use Exception;
use Illuminate\Support\Facades\DB;

class CompraService
{
    /**
     * @var CompraRepository $_repository
     */
    private CompraRepository $_repository;

    /**
     * @var CompraItemEstoqueService $_estoqueService
     */
    private CompraItemEstoqueService $_estoqueService;

    /**
     * @var CompraItemService $_compraItemService
     */
    private CompraItemService $_compraItemService;

    public function __construct()
    {
        $this->_repository            = new CompraRepository();
        $this->_estoqueService        = new CompraItemEstoqueService();
        $this->_compraItemService     = new CompraItemService();
    }

    // =========================================================
    // LOOKUPS
    // =========================================================

    public function handleLookupsCompra(): array
    {
        return [
            'plataformasCompra' => PlataformaCompra::whereNull('deleted_at')
                ->orderBy('descricao')
                ->get(['id', 'descricao']),
        ];
    }

    // =========================================================
    // HANDLE FUNCTIONS (orquestração + transação)
    // =========================================================

    public function handleAddCompra(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result         = (object) [];
            $result->compra = $this->createCompra($atributes);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleEditCompra(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result         = (object) [];
            $result->compra = $this->updateCompra($atributes);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleCancelarCompra(int|string $id): object
    {
        try {
            DB::beginTransaction();

            $result         = (object) [];
            $result->compra = $this->cancelarCompra($id);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleDeleteCompra(int|string $id): object
    {
        try {
            DB::beginTransaction();

            $result         = (object) [];
            $result->compra = $this->deleteCompra($id);

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

    public function createCompra(object $atributes): object
    {
        try {
            $this->validatePlataformaCompra((int) $atributes->id_plataforma_compra);

            $payload = $this->preparePayload($atributes);
            $newData = $this->_repository->create($payload);

            $this->persistCompraItens($newData->id, $atributes->compra_itens);

            return (object) [
                'data'    => $this->getCompraId($newData->id),
                'status'  => true,
                'message' => 'Compra cadastrada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function updateCompra(object $atributes): object
    {
        try {
            $record = $this->_repository->findById($atributes->id);

            if (!$record) {
                throw new Exception('Compra não encontrada', 404);
            }

            $this->validarCompraAtiva($record);

            $this->validatePlataformaCompra((int) $atributes->id_plataforma_compra);

            $payload = $this->preparePayload($atributes);
            $saved   = $this->_repository->update($record, $payload);

            if (!$saved) {
                throw new Exception('Não foi possível editar Compra', 500);
            }

            $this->_compraItemService->removeAllByCompraId($atributes->id);
            $this->persistCompraItens($atributes->id, $atributes->compra_itens);

            return (object) [
                'data'    => $this->getCompraId($atributes->id),
                'status'  => true,
                'message' => 'Compra alterada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function deleteCompra(int|string $id): object
    {
        throw new Exception(
            'Compras não podem ser excluídas fisicamente. Utilize o cancelamento lógico.',
            422
        );
    }

    public function cancelarCompra(int|string $id): object
    {
        try {
            $record = $this->_repository->findById($id);

            if (!$record) {
                throw new Exception('Compra não encontrada', 404);
            }

            if ($record->status === Compra::STATUS_CANCELADA) {
                throw new Exception('Esta compra já está cancelada.', 422);
            }

            $this->_estoqueService->cancelarLotesDaCompra($id);

            $saved = $this->_repository->update($record, ['status' => Compra::STATUS_CANCELADA]);

            if (!$saved) {
                throw new Exception('Não foi possível cancelar a Compra', 500);
            }

            return (object) [
                'data'    => $this->getCompraId($id),
                'status'  => true,
                'message' => 'Compra cancelada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    // =========================================================
    // QUERIES
    // =========================================================

    public function getCompraPaginate(object $atributes): array
    {
        $query = DB::query();

        $query->select(
            'ent.id',
            'ent.id_plataforma_compra',
            'plat.descricao as plataforma_compra_descricao',
            'ent.data_compra',
            'ent.numero_pedido',
            'ent.valor_frete',
            'ent.valor_desconto',
            'ent.valor_taxa',
            'ent.valor_imposto',
            'ent.valor_total',
            'ent.observacao',
            'ent.status',
            'ent.created_at',
        );

        $query->selectRaw('ent.status as badge_status');

        $query->from('compras as ent');
        $query->join('plataforma_compras as plat', 'plat.id', '=', 'ent.id_plataforma_compra');
        $query->whereNull('ent.deleted_at');
        $query->whereNull('plat.deleted_at');
        $query->orderByDesc('ent.data_compra');
        $query->orderByDesc('ent.id');

        if (!empty($atributes->id_plataforma_compra)) {
            $query->where('ent.id_plataforma_compra', $atributes->id_plataforma_compra);
        }

        if (!empty($atributes->data_compra)) {
            $query->whereDate('ent.data_compra', $atributes->data_compra);
        }

        if (!empty($atributes->numero_pedido)) {
            $chave = $atributes->numero_pedido;
            $query->where(function ($q) use ($chave) {
                $q->where('ent.numero_pedido', 'like', '%' . $chave . '%');
            });
        }

        if (!empty($atributes->status)) {
            $status = strtoupper((string) $atributes->status);

            if (in_array($status, [Compra::STATUS_ATIVA, Compra::STATUS_CANCELADA], true)) {
                $query->where('ent.status', $status);
            }
        }

        if (!empty($atributes->palavra_chave)) {
            $chave = $atributes->palavra_chave;
            $query->where(function ($q) use ($chave) {
                $q->where('ent.numero_pedido', 'like', '%' . $chave . '%')
                    ->orWhere('ent.observacao', 'like', '%' . $chave . '%')
                    ->orWhere('plat.descricao', 'like', '%' . $chave . '%');
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

    public function getCompraId(int|string $id): array
    {
        try {
            $query = DB::table('compras as ent')
                ->join('plataforma_compras as plat', 'plat.id', '=', 'ent.id_plataforma_compra')
                ->select(
                    'ent.id',
                    'ent.id_plataforma_compra',
                    'plat.descricao as plataforma_compra_descricao',
                    'ent.data_compra',
                    'ent.numero_pedido',
                    'ent.valor_frete',
                    'ent.valor_desconto as desconto',
                    'ent.valor_taxa',
                    'ent.valor_imposto',
                    'ent.valor_total',
                    'ent.observacao',
                    'ent.status',
                    'ent.created_at',
                )
                ->selectRaw('ent.status as badge_status')
                ->whereNull('ent.deleted_at')
                ->whereNull('plat.deleted_at')
                ->where('ent.id', $id);

            $record = $query->first();

            if (!$record) {
                throw new Exception('Compra não encontrada', 404);
            }

            $itens = DB::table('compras_itens as ci')
                ->join('itens as item', 'item.id', '=', 'ci.id_item')
                ->select(
                    'ci.id',
                    'ci.id_item',
                    'item.descricao as item_descricao',
                    'item.codigo as item_codigo',
                    'item.unidade_medida as item_unidade_medida',
                    'ci.qtd_compra',
                    'ci.qtd_interna',
                    'ci.qtd_original',
                    'ci.qtd_atual',
                    'ci.valor_unitario_compra',
                    'ci.valor_total',
                    'ci.valor_unitario_real',
                )
                ->whereNull('ci.deleted_at')
                ->whereNull('item.deleted_at')
                ->where('ci.id_compra', $id)
                ->orderBy('item.descricao')
                ->get()
                ->toArray();

            $idsCompraItens = array_column($itens, 'id');
            $idsItens       = array_values(array_unique(array_column($itens, 'id_item')));

            $movimentacoes = DB::table('movimentacoes_estoque as mov')
                ->join('itens as item', 'item.id', '=', 'mov.id_item')
                ->select(
                    'mov.id',
                    'mov.id_item',
                    'item.descricao as item_descricao',
                    'mov.id_compra_item',
                    'mov.tipo_movimentacao',
                    'mov.qtd',
                    'mov.saldo_anterior',
                    'mov.saldo_posterior',
                    'mov.observacao',
                    'mov.data_movimentacao',
                    'mov.created_at',
                )
                ->where(function ($query) use ($idsCompraItens, $idsItens, $id) {
                    if (!empty($idsCompraItens)) {
                        $query->whereIn('mov.id_compra_item', $idsCompraItens);
                    }

                    if (!empty($idsItens)) {
                        $query->orWhere(function ($sub) use ($idsItens, $id) {
                            $sub->where('mov.tipo_movimentacao', 'ENTRADA_COMPRA')
                                ->whereIn('mov.id_item', $idsItens)
                                ->where('mov.observacao', 'Entrada via compra #' . $id);
                        });
                    }
                })
                ->orderByDesc('mov.data_movimentacao')
                ->orderByDesc('mov.id')
                ->get()
                ->toArray();

            $data                     = collect($record)->toArray();
            $data['compra_itens']     = $itens;
            $data['itens']            = $itens;
            $data['movimentacoes']    = $movimentacoes;

            return $data;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function getCompraAsync(object $params): array
    {
        $query = DB::table('compras as ent')
            ->join('plataforma_compras as plat', 'plat.id', '=', 'ent.id_plataforma_compra')
            ->whereNull('ent.deleted_at')
            ->whereNull('plat.deleted_at')
            ->select(
                'ent.id',
                'ent.data_compra',
                'ent.numero_pedido',
                'ent.valor_total',
                'ent.status',
                'plat.descricao as plataforma_compra_descricao',
            )
            ->where('ent.status', Compra::STATUS_ATIVA)
            ->orderByDesc('ent.data_compra')
            ->orderByDesc('ent.id');

        if (!empty($params->palavra_chave)) {
            $chave = $params->palavra_chave;
            $query->where(function ($q) use ($chave) {
                $q->where('ent.numero_pedido', 'like', '%' . $chave . '%')
                    ->orWhere('plat.descricao', 'like', '%' . $chave . '%');
            });
            $query->limit(10);
        }

        return $query->get()->toArray();
    }

    // =========================================================
    // HELPERS
    // =========================================================

    private function validatePlataformaCompra(int $idPlataformaCompra): void
    {
        $exists = PlataformaCompra::where('id', $idPlataformaCompra)
            ->whereNull('deleted_at')
            ->exists();

        if (!$exists) {
            throw new Exception('Plataforma de Compra não encontrada', 422);
        }
    }

    private function validarCompraAtiva(Compra $compra): void
    {
        if ($compra->status === Compra::STATUS_CANCELADA) {
            throw new Exception('Não é possível alterar uma compra cancelada.', 422);
        }
    }

    private function preparePayload(object $atributes): array
    {
        return [
            'id_plataforma_compra' => (int) $atributes->id_plataforma_compra,
            'data_compra'          => $atributes->data_compra,
            'numero_pedido'        => $atributes->numero_pedido ?? null,
            'valor_frete'          => (float) ($atributes->valor_frete ?? 0),
            'valor_desconto'       => (float) ($atributes->valor_desconto ?? $atributes->desconto ?? 0),
            'valor_taxa'           => (float) ($atributes->valor_taxa ?? 0),
            'valor_imposto'        => (float) ($atributes->valor_imposto ?? 0),
            'valor_total'          => (float) $atributes->valor_total,
            'observacao'           => $atributes->observacao ?? null,
        ];
    }

    private function persistCompraItens(int|string $idCompra, array $itens): void
    {
        foreach ($itens as $item) {
            $itemAttributes = is_array($item) ? (object) $item : $item;
            $this->_compraItemService->createItemForCompra($itemAttributes, (int) $idCompra);
        }
    }
}
