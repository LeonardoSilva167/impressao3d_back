<?php

namespace App\Repositories\Compra;

use App\Models\Compra;

class CompraRepository
{
    public function findById(int|string $id): ?Compra
    {
        return Compra::where('id', $id)->whereNull('deleted_at')->first();
    }

    public function create(array $data): Compra
    {
        return Compra::create($data);
    }

    public function update(Compra $compra, array $data): bool
    {
        return $compra->update($data);
    }

    public function delete(Compra $compra): bool
    {
        return (bool) $compra->delete();
    }
}
