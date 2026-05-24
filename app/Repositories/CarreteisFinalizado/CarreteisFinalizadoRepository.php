<?php

namespace App\Repositories\CarreteisFinalizado;

use App\Models\CarreteisFinalizado;

class CarreteisFinalizadoRepository
{
    public function findById(int|string $id): ?CarreteisFinalizado
    {
        return CarreteisFinalizado::whereNull('deleted_at')->find($id);
    }

    public function create(array $data): CarreteisFinalizado
    {
        return CarreteisFinalizado::create($data);
    }

    public function update(CarreteisFinalizado $registro, array $data): bool
    {
        return $registro->update($data);
    }

    public function delete(CarreteisFinalizado $registro): bool
    {
        return (bool) $registro->delete();
    }
}
