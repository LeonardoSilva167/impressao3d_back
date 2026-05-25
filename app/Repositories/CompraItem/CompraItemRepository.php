<?php

namespace App\Repositories\CompraItem;

use App\Models\Compra;
use App\Models\CompraItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CompraItemRepository
{
    public function findById(int|string $id): ?CompraItem
    {
        return CompraItem::where('id', $id)->whereNull('deleted_at')->first();
    }

    public function findByCompraId(int|string $idCompra): Collection
    {
        return CompraItem::where('id_compra', $idCompra)
            ->whereNull('deleted_at')
            ->get();
    }

    public function findByIdForUpdate(int|string $id): ?CompraItem
    {
        return CompraItem::where('id', $id)
            ->whereNull('deleted_at')
            ->lockForUpdate()
            ->first();
    }

    public function create(array $data): CompraItem
    {
        return CompraItem::create($data);
    }

    public function update(CompraItem $compraItem, array $data): bool
    {
        return $compraItem->update($data);
    }

    public function delete(CompraItem $compraItem): bool
    {
        return (bool) $compraItem->delete();
    }

    public function sumEstoqueEPrecoMedioByItemId(int|string $idItem): array
    {
        $lotes = CompraItem::query()
            ->join('compras as comp', 'comp.id', '=', 'compras_itens.id_compra')
            ->where('compras_itens.id_item', $idItem)
            ->whereNull('compras_itens.deleted_at')
            ->whereNull('comp.deleted_at')
            ->where('comp.status', Compra::STATUS_ATIVA)
            ->get(['compras_itens.qtd_atual', 'compras_itens.valor_unitario_real']);

        $estoque   = 0;
        $somaCusto = 0;
        $somaQtd   = 0;

        foreach ($lotes as $lote) {
            $qtdAtual = (float) $lote->qtd_atual;
            $estoque += $qtdAtual;

            if ($qtdAtual > 0) {
                $somaCusto += $qtdAtual * (float) $lote->valor_unitario_real;
                $somaQtd += $qtdAtual;
            }
        }

        return [
            'estoque'    => $estoque,
            'preco_medio' => $somaQtd > 0 ? $somaCusto / $somaQtd : 0,
        ];
    }

    public function findLotesComEstoqueByItemIdOrderedFifo(int|string $idItem): Collection
    {
        return $this->queryLotesComEstoqueFifo($idItem)
            ->lockForUpdate()
            ->get();
    }

    public function findLotesComEstoqueByItemIdOrderedFifoReadOnly(int|string $idItem): Collection
    {
        return $this->queryLotesComEstoqueFifo($idItem)
            ->with(['compra.plataformaCompra'])
            ->get();
    }

    public function findLoteMaisAntigoComEstoque(int|string $idItem): ?CompraItem
    {
        return $this->queryLotesComEstoqueFifo($idItem)->first();
    }

    private function queryLotesComEstoqueFifo(int|string $idItem): Builder
    {
        return CompraItem::query()
            ->join('compras as comp', 'comp.id', '=', 'compras_itens.id_compra')
            ->where('compras_itens.id_item', $idItem)
            ->whereNull('compras_itens.deleted_at')
            ->whereNull('comp.deleted_at')
            ->where('comp.status', Compra::STATUS_ATIVA)
            ->where('compras_itens.qtd_atual', '>', 0)
            ->orderBy('comp.data_compra')
            ->orderBy('compras_itens.id')
            ->select('compras_itens.*');
    }
}
