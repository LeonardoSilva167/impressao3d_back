<?php

namespace App\Repositories\GradeProdutoItem;

use App\Models\GradeProdutoItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GradeProdutoItemRepository
{
    public function create(array $data): GradeProdutoItem
    {
        return GradeProdutoItem::create($data);
    }

    public function deleteByGradeId(int $idGrade): void
    {
        GradeProdutoItem::where('id_grade_produto', $idGrade)
            ->whereNull('deleted_at')
            ->get()
            ->each(fn (GradeProdutoItem $item) => $item->delete());
    }

    public function getByGradeId(int $idGrade): Collection
    {
        return DB::table('grade_produto_itens as gpi')
            ->select(
                'gpi.id',
                'gpi.id_grade_produto',
                'gpi.nome_produto',
                'gpi.sku',
                'gpi.peso_total',
                'gpi.tempo_total',
                'gpi.custo_filamento',
                'gpi.custo_energia',
                'gpi.custo_desgaste',
                'gpi.custo_total',
                'gpi.status',
                'gpi.created_at',
            )
            ->where('gpi.id_grade_produto', $idGrade)
            ->whereNull('gpi.deleted_at')
            ->orderBy('gpi.nome_produto')
            ->get();
    }

    public function countByGradeId(int $idGrade): int
    {
        return GradeProdutoItem::where('id_grade_produto', $idGrade)
            ->whereNull('deleted_at')
            ->count();
    }
}
