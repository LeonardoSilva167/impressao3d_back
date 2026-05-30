<?php

namespace App\Repositories\GradeProdutoParte;

use App\Models\GradeProdutoParte;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GradeProdutoParteRepository
{
    public function create(array $data): GradeProdutoParte
    {
        return GradeProdutoParte::create($data);
    }

    public function deleteByGradeId(int $idGrade): void
    {
        GradeProdutoParte::where('id_grade_produto', $idGrade)
            ->whereNull('deleted_at')
            ->get()
            ->each(fn (GradeProdutoParte $parte) => $parte->delete());
    }

    public function getByGradeId(int $idGrade): Collection
    {
        return DB::table('grade_produto_partes as gpp')
            ->select(
                'gpp.id',
                'gpp.id_grade_produto',
                'gpp.id_parte_projeto',
                'parte.nome_parte',
            )
            ->join('projetos_impressao_partes as parte', 'parte.id', '=', 'gpp.id_parte_projeto')
            ->where('gpp.id_grade_produto', $idGrade)
            ->whereNull('gpp.deleted_at')
            ->whereNull('parte.deleted_at')
            ->orderBy('parte.nome_parte')
            ->get();
    }

    public function getIdsPartesByGradeId(int $idGrade): array
    {
        return GradeProdutoParte::where('id_grade_produto', $idGrade)
            ->whereNull('deleted_at')
            ->pluck('id_parte_projeto')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }
}
