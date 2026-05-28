<?php

namespace App\Repositories\ProjetoImpressaoParte;

use App\Models\ProjetoImpressaoParte;

class ProjetoImpressaoParteRepository
{
    public function findById(int|string $id): ?ProjetoImpressaoParte
    {
        return ProjetoImpressaoParte::where('id', $id)->whereNull('deleted_at')->first();
    }

    public function create(array $data): ProjetoImpressaoParte
    {
        return ProjetoImpressaoParte::create($data);
    }

    public function update(ProjetoImpressaoParte $parte, array $data): bool
    {
        return $parte->update($data);
    }

    public function delete(ProjetoImpressaoParte $parte): bool
    {
        return (bool) $parte->delete();
    }
}
