<?php

namespace App\Repositories\Item;

use App\Models\Item;

class ItemRepository
{
    public function findById(int|string $id): ?Item
    {
        return Item::where('id', $id)->whereNull('deleted_at')->first();
    }

    public function findByIdForUpdate(int|string $id): ?Item
    {
        return Item::where('id', $id)
            ->whereNull('deleted_at')
            ->lockForUpdate()
            ->first();
    }

    public function update(Item $item, array $data): bool
    {
        return $item->update($data);
    }
}
