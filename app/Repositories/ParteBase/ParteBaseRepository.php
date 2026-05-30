<?php

namespace App\Repositories\ParteBase;

use App\Models\ParteBase;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ParteBaseRepository
{
    public function findById(int|string $id): ?ParteBase
    {
        return ParteBase::where('id', $id)->first();
    }

    public function findByCodigo(string $codigo, int|string|null $excludeId = null): ?ParteBase
    {
        $query = ParteBase::where('codigo', $codigo);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->first();
    }

    public function create(array $data): ParteBase
    {
        return ParteBase::create($data);
    }

    public function update(ParteBase $record, array $data): bool
    {
        return $record->update($data);
    }

    public function delete(ParteBase $record): bool
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
            ->from('partes_base as ent')
            ->whereNull('ent.deleted_at')
            ->orderBy('ent.descricao');
    }

    public function getByIdQuery(int|string $id): Builder
    {
        return DB::table('partes_base as ent')
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
        return DB::table('partes_base as ent')
            ->whereNull('ent.deleted_at')
            ->select('ent.id', 'ent.descricao', 'ent.codigo')
            ->orderBy('ent.descricao');
    }
}
