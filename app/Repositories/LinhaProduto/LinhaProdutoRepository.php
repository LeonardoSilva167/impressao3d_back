<?php

namespace App\Repositories\LinhaProduto;

use App\Models\LinhaProduto;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class LinhaProdutoRepository
{
    public function findById(int|string $id): ?LinhaProduto
    {
        return LinhaProduto::where('id', $id)->first();
    }

    public function findByCodigo(string $codigo, int|string|null $excludeId = null): ?LinhaProduto
    {
        $query = LinhaProduto::where('codigo', $codigo);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->first();
    }

    public function create(array $data): LinhaProduto
    {
        return LinhaProduto::create($data);
    }

    public function update(LinhaProduto $record, array $data): bool
    {
        return $record->update($data);
    }

    public function delete(LinhaProduto $record): bool
    {
        return (bool) $record->delete();
    }

    public function getPaginateQuery(): Builder
    {
        return DB::query()
            ->select(
                'ent.id',
                'ent.descricao',
                'ent.codigo',
                'ent.created_at',
            )
            ->from('linhas_produtos as ent')
            ->whereNull('ent.deleted_at')
            ->orderBy('ent.descricao');
    }

    public function getByIdQuery(int|string $id): Builder
    {
        return DB::table('linhas_produtos as ent')
            ->select(
                'ent.id',
                'ent.descricao',
                'ent.codigo',
                'ent.created_at',
            )
            ->whereNull('ent.deleted_at')
            ->where('ent.id', $id);
    }

    public function getAsyncQuery(): Builder
    {
        return DB::table('linhas_produtos as ent')
            ->whereNull('ent.deleted_at')
            ->select('ent.id', 'ent.descricao', 'ent.codigo')
            ->orderBy('ent.descricao');
    }
}
