<?php

namespace App\Repositories\GradeProduto;

use App\Models\GradeProduto;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class GradeProdutoRepository
{
    public function findById(int|string $id): ?GradeProduto
    {
        return GradeProduto::where('id', $id)->whereNull('deleted_at')->first();
    }

    public function create(array $data): GradeProduto
    {
        return GradeProduto::create($data);
    }

    public function update(GradeProduto $record, array $data): bool
    {
        return $record->update($data);
    }

    public function delete(GradeProduto $record): bool
    {
        return (bool) $record->delete();
    }

    public function getPaginateQuery(): Builder
    {
        return DB::query()
            ->select(
                'gp.id',
                'gp.id_produto_base',
                'gp.descricao',
                'gp.status',
                'gp.created_at',
                'pb.descricao_produto',
                'pb.sku_base',
                DB::raw('(SELECT COUNT(*) FROM grade_produto_itens gpi WHERE gpi.id_grade_produto = gp.id AND gpi.deleted_at IS NULL) as quantidade_produtos'),
            )
            ->from('grades_produtos as gp')
            ->join('produtos_base as pb', 'pb.id', '=', 'gp.id_produto_base')
            ->whereNull('gp.deleted_at')
            ->whereNull('pb.deleted_at')
            ->orderByDesc('gp.created_at');
    }

    public function findByIdWithRelations(int|string $id): ?object
    {
        return DB::table('grades_produtos as gp')
            ->select(
                'gp.id',
                'gp.id_produto_base',
                'gp.descricao',
                'gp.status',
                'gp.created_at',
                'gp.updated_at',
                'pb.descricao_produto',
                'pb.sku_base',
                'pb.codigo_base',
            )
            ->join('produtos_base as pb', 'pb.id', '=', 'gp.id_produto_base')
            ->whereNull('gp.deleted_at')
            ->whereNull('pb.deleted_at')
            ->where('gp.id', $id)
            ->first();
    }

    public function getAsyncQuery(): Builder
    {
        return DB::table('grades_produtos as gp')
            ->join('produtos_base as pb', 'pb.id', '=', 'gp.id_produto_base')
            ->whereNull('gp.deleted_at')
            ->whereNull('pb.deleted_at')
            ->select(
                'gp.id',
                'gp.descricao',
                'pb.descricao_produto',
                'pb.sku_base',
            )
            ->orderBy('gp.descricao');
    }
}
