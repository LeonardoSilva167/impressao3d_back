<?php

namespace App\Repositories\CompraItem;

use App\Models\CompraItem;
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
        $lotes = CompraItem::where('id_item', $idItem)
            ->whereNull('deleted_at')
            ->get(['qtd_atual', 'valor_unitario_real']);

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
        return CompraItem::where('id_item', $idItem)
            ->whereNull('deleted_at')
            ->where('qtd_atual', '>', 0)
            ->orderBy('created_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    public function findLoteMaisAntigoComEstoque(int|string $idItem): ?CompraItem
    {
        return CompraItem::where('id_item', $idItem)
            ->whereNull('deleted_at')
            ->where('qtd_atual', '>', 0)
            ->orderBy('created_at')
            ->orderBy('id')
            ->first();
    }
}
