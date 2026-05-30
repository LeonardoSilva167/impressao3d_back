<?php

namespace App\Repositories\GradeProdutoCombinacaoParte;

use App\Models\GradeProdutoCombinacaoParte;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GradeProdutoCombinacaoParteRepository
{
    public function create(array $data): GradeProdutoCombinacaoParte
    {
        return GradeProdutoCombinacaoParte::create($data);
    }

    public function deleteByCombinacaoIds(array $idsCombinacao): void
    {
        if (empty($idsCombinacao)) {
            return;
        }

        GradeProdutoCombinacaoParte::whereIn('id_grade_produto_combinacao', $idsCombinacao)
            ->whereNull('deleted_at')
            ->get()
            ->each(fn (GradeProdutoCombinacaoParte $parte) => $parte->delete());
    }

    public function getByCombinacaoId(int $idCombinacao): Collection
    {
        return DB::table('grade_produto_combinacao_partes as gpcp')
            ->select(
                'gpcp.id',
                'gpcp.id_grade_produto_combinacao',
                'gpcp.id_parte_projeto',
                'gpcp.quantidade',
                'parte.nome_parte',
            )
            ->join('projetos_impressao_partes as parte', 'parte.id', '=', 'gpcp.id_parte_projeto')
            ->where('gpcp.id_grade_produto_combinacao', $idCombinacao)
            ->whereNull('gpcp.deleted_at')
            ->whereNull('parte.deleted_at')
            ->orderBy('gpcp.id')
            ->get();
    }

    public function getByGradeId(int $idGrade): Collection
    {
        return DB::table('grade_produto_combinacao_partes as gpcp')
            ->select(
                'gpcp.id',
                'gpcp.id_grade_produto_combinacao',
                'gpcp.id_parte_projeto',
                'gpcp.quantidade',
                'parte.nome_parte',
                'gpc.descricao as descricao_combinacao',
            )
            ->join('grade_produto_combinacoes as gpc', 'gpc.id', '=', 'gpcp.id_grade_produto_combinacao')
            ->join('projetos_impressao_partes as parte', 'parte.id', '=', 'gpcp.id_parte_projeto')
            ->where('gpc.id_grade_produto', $idGrade)
            ->whereNull('gpcp.deleted_at')
            ->whereNull('gpc.deleted_at')
            ->whereNull('parte.deleted_at')
            ->orderBy('gpc.id')
            ->orderBy('gpcp.id')
            ->get();
    }
}
