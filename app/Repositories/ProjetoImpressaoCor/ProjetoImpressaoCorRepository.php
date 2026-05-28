<?php

namespace App\Repositories\ProjetoImpressaoCor;

use App\Models\ProjetoImpressaoCor;

class ProjetoImpressaoCorRepository
{
    public function create(array $data): ProjetoImpressaoCor
    {
        return ProjetoImpressaoCor::create($data);
    }

    public function removeAllByProjetoId(int|string $idProjeto): void
    {
        ProjetoImpressaoCor::where('id_projeto_impressao', $idProjeto)
            ->whereNull('deleted_at')
            ->get()
            ->each(fn (ProjetoImpressaoCor $cor) => $cor->delete());
    }

    public function deleteCoresByProjeto(int $idProjeto): void
    {
        $this->removeAllByProjetoId($idProjeto);
    }
}
