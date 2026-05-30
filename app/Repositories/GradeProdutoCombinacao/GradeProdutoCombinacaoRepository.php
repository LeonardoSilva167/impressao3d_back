<?php

namespace App\Repositories\GradeProdutoCombinacao;

use App\Models\GradeProdutoCombinacao;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GradeProdutoCombinacaoRepository
{
    public function create(array $data): GradeProdutoCombinacao
    {
        return GradeProdutoCombinacao::create($data);
    }

    public function deleteByGradeId(int $idGrade): void
    {
        GradeProdutoCombinacao::where('id_grade_produto', $idGrade)
            ->whereNull('deleted_at')
            ->get()
            ->each(fn (GradeProdutoCombinacao $combinacao) => $combinacao->delete());
    }

    public function getByGradeId(int $idGrade): Collection
    {
        return DB::table('grade_produto_combinacoes as gpc')
            ->select(
                'gpc.id',
                'gpc.id_grade_produto',
                'gpc.descricao',
                'gpc.created_at',
            )
            ->where('gpc.id_grade_produto', $idGrade)
            ->whereNull('gpc.deleted_at')
            ->orderBy('gpc.id')
            ->get();
    }
}
