<?php

namespace App\Repositories\Filamento;

use App\Models\Filamento;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class FilamentoRepository
{
    public function findById(int|string $id): ?Filamento
    {
        return Filamento::where('id', $id)->first();
    }

    public function findByCombinacao(
        int $idTipoMaterial,
        int $idCor,
        int $idLinhaMarca,
        int $idMarca,
        int|string|null $excludeId = null
    ): ?Filamento {
        $query = Filamento::where('id_tipo_material', $idTipoMaterial)
            ->where('id_cor', $idCor)
            ->where('id_linha_marca', $idLinhaMarca)
            ->where('id_marca', $idMarca);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->first();
    }

    public function create(array $data): Filamento
    {
        return Filamento::create($data);
    }

    public function update(Filamento $filamento, array $data): bool
    {
        return $filamento->update($data);
    }

    public function delete(Filamento $filamento): bool
    {
        return (bool) $filamento->delete();
    }

    public function generateNextCodigo(): string
    {
        $lastId = Filamento::withTrashed()->lockForUpdate()->max('id') ?? 0;
        $next   = $lastId + 1;

        return 'FIL-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    public function getDescricoesRelacionamentos(
        int $idTipoMaterial,
        int $idCor,
        int $idLinhaMarca,
        int $idMarca
    ): ?object {
        $tipoMaterial = DB::table('tipos_materiais')
            ->where('id', $idTipoMaterial)
            ->whereNull('deleted_at')
            ->value('descricao');

        $cor = DB::table('cores')
            ->where('id', $idCor)
            ->whereNull('deleted_at')
            ->value('descricao');

        $linhaMarca = DB::table('linhas_marcas')
            ->where('id', $idLinhaMarca)
            ->whereNull('deleted_at')
            ->value('descricao');

        $marca = DB::table('marcas')
            ->where('id', $idMarca)
            ->whereNull('deleted_at')
            ->value('descricao');

        if ($tipoMaterial === null || $cor === null || $linhaMarca === null || $marca === null) {
            return null;
        }

        return (object) [
            'tipo_material_descricao' => $tipoMaterial,
            'cor_descricao'           => $cor,
            'linha_marca_descricao'   => $linhaMarca,
            'marca_descricao'         => $marca,
        ];
    }

    public function getPaginateQuery(): Builder
    {
        return DB::query()
            ->select(
                'ent.id',
                'ent.id_tipo_material',
                'ent.id_cor',
                'ent.id_linha_marca',
                'ent.id_marca',
                'ent.codigo',
                'ent.resumo',
                'ent.qtd',
                'ent.preco_medio_grama',
                'ent.created_at',
                'tm.descricao as tipo_material_descricao',
                'c.descricao as cor_descricao',
                'c.hexadecimal as cor_hexadecimal',
                'lm.descricao as linha_marca_descricao',
                'm.descricao as marca_descricao',
            )
            ->from('filamentos as ent')
            ->join('tipos_materiais as tm', 'tm.id', '=', 'ent.id_tipo_material')
            ->join('cores as c', 'c.id', '=', 'ent.id_cor')
            ->join('linhas_marcas as lm', 'lm.id', '=', 'ent.id_linha_marca')
            ->join('marcas as m', 'm.id', '=', 'ent.id_marca')
            ->whereNull('ent.deleted_at')
            ->whereNull('tm.deleted_at')
            ->whereNull('c.deleted_at')
            ->whereNull('lm.deleted_at')
            ->whereNull('m.deleted_at')
            ->orderBy('ent.resumo');
    }

    public function findByIdWithRelations(int|string $id): ?object
    {
        return DB::table('filamentos as ent')
            ->select(
                'ent.id',
                'ent.id_tipo_material',
                'ent.id_cor',
                'ent.id_linha_marca',
                'ent.id_marca',
                'ent.codigo',
                'ent.resumo',
                'ent.qtd',
                'ent.preco_medio_grama',
                'ent.created_at',
                'tm.descricao as tipo_material_descricao',
                'c.descricao as cor_descricao',
                'lm.descricao as linha_marca_descricao',
                'm.descricao as marca_descricao',
            )
            ->join('tipos_materiais as tm', 'tm.id', '=', 'ent.id_tipo_material')
            ->join('cores as c', 'c.id', '=', 'ent.id_cor')
            ->join('linhas_marcas as lm', 'lm.id', '=', 'ent.id_linha_marca')
            ->join('marcas as m', 'm.id', '=', 'ent.id_marca')
            ->whereNull('ent.deleted_at')
            ->whereNull('tm.deleted_at')
            ->whereNull('c.deleted_at')
            ->whereNull('lm.deleted_at')
            ->whereNull('m.deleted_at')
            ->where('ent.id', $id)
            ->first();
    }

    public function getAsyncQuery(): Builder
    {
        return DB::table('filamentos as ent')
            ->whereNull('ent.deleted_at')
            ->select('ent.id', 'ent.codigo', 'ent.resumo')
            ->orderBy('ent.resumo');
    }
}
