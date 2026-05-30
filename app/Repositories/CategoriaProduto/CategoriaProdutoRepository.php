<?php

namespace App\Repositories\CategoriaProduto;

use App\Models\CategoriaProduto;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class CategoriaProdutoRepository
{
    public function findById(int|string $id): ?CategoriaProduto
    {
        return CategoriaProduto::where('id', $id)->first();
    }

    public function findByCodigo(string $codigo, int|string|null $excludeId = null): ?CategoriaProduto
    {
        $query = CategoriaProduto::where('codigo', $codigo);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->first();
    }

    public function create(array $data): CategoriaProduto
    {
        return CategoriaProduto::create($data);
    }

    public function update(CategoriaProduto $record, array $data): bool
    {
        return $record->update($data);
    }

    public function delete(CategoriaProduto $record): bool
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
            ->from('categorias_produtos as ent')
            ->whereNull('ent.deleted_at')
            ->orderBy('ent.descricao');
    }

    public function getByIdQuery(int|string $id): Builder
    {
        return DB::table('categorias_produtos as ent')
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
        return DB::table('categorias_produtos as ent')
            ->whereNull('ent.deleted_at')
            ->select('ent.id', 'ent.descricao', 'ent.codigo')
            ->orderBy('ent.descricao');
    }
}
