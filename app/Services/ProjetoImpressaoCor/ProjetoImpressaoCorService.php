<?php

namespace App\Services\ProjetoImpressaoCor;

use App\Repositories\ProjetoImpressaoCor\ProjetoImpressaoCorRepository;
use Exception;
use Illuminate\Support\Facades\DB;

class ProjetoImpressaoCorService
{
    /**
     * @var ProjetoImpressaoCorRepository $_repository
     */
    private ProjetoImpressaoCorRepository $_repository;

    public function __construct()
    {
        $this->_repository = new ProjetoImpressaoCorRepository();
    }

    public function persistCores(int $idProjeto, array $cores): void
    {
        foreach ($cores as $cor) {
            $attributes = is_array($cor) ? (object) $cor : $cor;

            $this->_repository->create([
                'id_projeto_impressao' => $idProjeto,
                'id_cor'               => (int) $attributes->id_cor,
                'peso_gramas'          => round((float) $attributes->peso_gramas, 2),
            ]);
        }
    }

    public function syncCores(int $idProjeto, array $cores): void
    {
        $this->_repository->removeAllByProjetoId($idProjeto);
        $this->persistCores($idProjeto, $cores);
    }

    public function deleteCoresByProjeto(int $idProjeto): void
    {
        $this->_repository->deleteCoresByProjeto($idProjeto);
    }

    public function getCoresByProjeto(int $idProjeto): array
    {
        return DB::table('projetos_impressao_cores as pic')
            ->join('cores as cor', 'cor.id', '=', 'pic.id_cor')
            ->select(
                'pic.id',
                'pic.id_cor',
                'cor.descricao as cor_descricao',
                'cor.codigo as cor_codigo',
                'cor.hexadecimal as cor_hexadecimal',
                'pic.peso_gramas',
            )
            ->whereNull('pic.deleted_at')
            ->whereNull('cor.deleted_at')
            ->where('pic.id_projeto_impressao', $idProjeto)
            ->orderBy('cor.descricao')
            ->get()
            ->map(fn ($item) => (array) $item)
            ->toArray();
    }

    public function validarSomaPesos(array $cores, float $pesoTotalGramas): void
    {
        $soma = 0.0;

        foreach ($cores as $cor) {
            $attributes = is_array($cor) ? (object) $cor : $cor;
            $soma += (float) $attributes->peso_gramas;
        }

        if (round($soma, 2) !== round($pesoTotalGramas, 2)) {
            throw new Exception(
                'A soma dos pesos das cores deve ser igual ao peso total do projeto.',
                422
            );
        }
    }
}
