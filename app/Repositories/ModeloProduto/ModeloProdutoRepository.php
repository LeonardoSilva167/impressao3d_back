<?php

namespace App\Repositories\ModeloProduto;

use App\Models\ModeloProduto;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ModeloProdutoRepository
{
    public function findById(int|string $id): ?ModeloProduto
    {
        return ModeloProduto::where('id', $id)->first();
    }

    public function findByCodigo(string $codigo, int|string|null $excludeId = null): ?ModeloProduto
    {
        $query = ModeloProduto::where('codigo', $codigo);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->first();
    }

    public function create(array $data): ModeloProduto
    {
        return ModeloProduto::create($data);
    }

    public function update(ModeloProduto $record, array $data): bool
    {
        return $record->update($data);
    }

    public function delete(ModeloProduto $record): bool
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
            ->from('modelos_produtos as ent')
            ->whereNull('ent.deleted_at')
            ->orderBy('ent.descricao');
    }

    public function getByIdQuery(int|string $id): Builder
    {
        return DB::table('modelos_produtos as ent')
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
        return DB::table('modelos_produtos as ent')
            ->whereNull('ent.deleted_at')
            ->select('ent.id', 'ent.descricao', 'ent.codigo')
            ->orderBy('ent.descricao');
    }
}
