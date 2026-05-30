<?php

namespace App\Repositories\ProjetoImpressaoParteItem;

use App\Models\ProjetoImpressaoParteItem;

class ProjetoImpressaoParteItemRepository
{
    public function findById(int|string $id): ?ProjetoImpressaoParteItem
    {
        return ProjetoImpressaoParteItem::where('id', $id)->whereNull('deleted_at')->first();
    }

    public function create(array $data): ProjetoImpressaoParteItem
    {
        return ProjetoImpressaoParteItem::create($data);
    }

    public function update(ProjetoImpressaoParteItem $item, array $data): bool
    {
        return $item->update($data);
    }

    public function delete(ProjetoImpressaoParteItem $item): bool
    {
        return (bool) $item->delete();
    }
}
