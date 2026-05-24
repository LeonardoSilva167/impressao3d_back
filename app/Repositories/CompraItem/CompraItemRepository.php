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
}
