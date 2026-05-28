<?php

namespace App\Repositories\ProjetoImpressao;

use App\Models\ProjetoImpressao;

class ProjetoImpressaoRepository
{
    public function findById(int|string $id): ?ProjetoImpressao
    {
        return ProjetoImpressao::where('id', $id)->whereNull('deleted_at')->first();
    }

    public function findByCodigoProjeto(string $codigo, ?int $ignoreId = null): ?ProjetoImpressao
    {
        $query = ProjetoImpressao::where('codigo_projeto', $codigo)->whereNull('deleted_at');

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->first();
    }

    public function create(array $data): ProjetoImpressao
    {
        return ProjetoImpressao::create($data);
    }

    public function update(ProjetoImpressao $projeto, array $data): bool
    {
        return $projeto->update($data);
    }

    public function delete(ProjetoImpressao $projeto): bool
    {
        return (bool) $projeto->delete();
    }
}
