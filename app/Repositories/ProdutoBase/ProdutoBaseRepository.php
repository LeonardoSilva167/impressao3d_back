<?php

namespace App\Repositories\ProdutoBase;

use App\Models\ProdutoBase;
use App\Models\ProdutoVariacao;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProdutoBaseRepository
{
    public function findById(int|string $id): ?ProdutoBase
    {
        return ProdutoBase::where('id', $id)->first();
    }

    public function findBySkuBase(string $skuBase, int|string|null $excludeId = null): ?ProdutoBase
    {
        $query = ProdutoBase::where('sku_base', $skuBase);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->first();
    }

    public function create(array $data): ProdutoBase
    {
        return ProdutoBase::create($data);
    }

    public function update(ProdutoBase $record, array $data): bool
    {
        return $record->update($data);
    }

    public function delete(ProdutoBase $record): bool
    {
        return (bool) $record->delete();
    }

    public function getCodigosRelacionamentos(int $idCategoria, int $idModelo, ?int $idLinha = null): ?object
    {
        $categoria = DB::table('categorias_produtos')
            ->where('id', $idCategoria)
            ->whereNull('deleted_at')
            ->value('codigo');

        $modelo = DB::table('modelos_produtos')
            ->where('id', $idModelo)
            ->whereNull('deleted_at')
            ->value('codigo');

        if ($categoria === null || $modelo === null) {
            return null;
        }

        $result = (object) [
            'codigo_categoria' => $categoria,
            'codigo_modelo'    => $modelo,
            'codigo_linha'     => null,
        ];

        if ($idLinha !== null) {
            $linha = DB::table('linhas_produtos')
                ->where('id', $idLinha)
                ->whereNull('deleted_at')
                ->value('codigo');

            if ($linha === null) {
                return null;
            }

            $result->codigo_linha = $linha;
        }

        return $result;
    }

    public function getPaginateQuery(): Builder
    {
        return DB::query()
            ->select(
                'ent.id',
                'ent.descricao_produto',
                'ent.codigo_base',
                'ent.sku_base',
                'ent.id_categoria',
                'ent.id_modelo',
                'ent.id_linha',
                'ent.created_at',
                'cp.descricao as categoria_descricao',
                'cp.codigo as categoria_codigo',
                'mp.descricao as modelo_descricao',
                'mp.codigo as modelo_codigo',
                'lp.descricao as linha_descricao',
                'lp.codigo as linha_codigo',
                DB::raw('(SELECT COUNT(*) FROM produto_variacoes pv WHERE pv.id_produto_base = ent.id AND pv.deleted_at IS NULL AND pv.status = \'ATIVA\') as quantidade_variacoes'),
            )
            ->from('produtos_base as ent')
            ->join('categorias_produtos as cp', 'cp.id', '=', 'ent.id_categoria')
            ->join('modelos_produtos as mp', 'mp.id', '=', 'ent.id_modelo')
            ->leftJoin('linhas_produtos as lp', 'lp.id', '=', 'ent.id_linha')
            ->whereNull('ent.deleted_at')
            ->whereNull('cp.deleted_at')
            ->whereNull('mp.deleted_at')
            ->where(function ($q) {
                $q->whereNull('ent.id_linha')
                    ->orWhereNull('lp.deleted_at');
            })
            ->orderBy('ent.descricao_produto');
    }

    public function findByIdWithRelations(int|string $id): ?object
    {
        return DB::table('produtos_base as ent')
            ->select(
                'ent.id',
                'ent.descricao_produto',
                'ent.codigo_base',
                'ent.sku_base',
                'ent.id_categoria',
                'ent.id_modelo',
                'ent.id_linha',
                'ent.created_at',
                'cp.descricao as categoria_descricao',
                'cp.codigo as categoria_codigo',
                'mp.descricao as modelo_descricao',
                'mp.codigo as modelo_codigo',
                'lp.descricao as linha_descricao',
                'lp.codigo as linha_codigo',
            )
            ->join('categorias_produtos as cp', 'cp.id', '=', 'ent.id_categoria')
            ->join('modelos_produtos as mp', 'mp.id', '=', 'ent.id_modelo')
            ->leftJoin('linhas_produtos as lp', 'lp.id', '=', 'ent.id_linha')
            ->whereNull('ent.deleted_at')
            ->whereNull('cp.deleted_at')
            ->whereNull('mp.deleted_at')
            ->where(function ($q) {
                $q->whereNull('ent.id_linha')
                    ->orWhereNull('lp.deleted_at');
            })
            ->where('ent.id', $id)
            ->first();
    }

    public function getVariacoesByProdutoBaseId(int $idProdutoBase, bool $apenasAtivas = false): Collection
    {
        $query = DB::table('produto_variacoes as pv')
            ->select(
                'pv.id',
                'pv.id_produto_base',
                'pv.id_cor_primaria',
                'pv.id_cor_secundaria',
                'pv.id_cor_terciaria',
                'pv.sku',
                'pv.status',
                'pv.created_at',
                'cp.descricao as cor_primaria_descricao',
                'cp.codigo as cor_primaria_codigo',
                'cp.hexadecimal as cor_primaria_hexadecimal',
                'cs.descricao as cor_secundaria_descricao',
                'cs.codigo as cor_secundaria_codigo',
                'cs.hexadecimal as cor_secundaria_hexadecimal',
                'ct.descricao as cor_terciaria_descricao',
                'ct.codigo as cor_terciaria_codigo',
                'ct.hexadecimal as cor_terciaria_hexadecimal',
            )
            ->join('cores as cp', 'cp.id', '=', 'pv.id_cor_primaria')
            ->leftJoin('cores as cs', 'cs.id', '=', 'pv.id_cor_secundaria')
            ->leftJoin('cores as ct', 'ct.id', '=', 'pv.id_cor_terciaria')
            ->whereNull('pv.deleted_at')
            ->whereNull('cp.deleted_at')
            ->where('pv.id_produto_base', $idProdutoBase)
            ->where(function ($q) {
                $q->whereNull('pv.id_cor_secundaria')
                    ->orWhereNull('cs.deleted_at');
            })
            ->where(function ($q) {
                $q->whereNull('pv.id_cor_terciaria')
                    ->orWhereNull('ct.deleted_at');
            })
            ->orderBy('pv.sku');

        if ($apenasAtivas) {
            $query->where('pv.status', ProdutoVariacao::STATUS_ATIVA);
        }

        return $query->get();
    }

    public function getAsyncQuery(): Builder
    {
        return DB::table('produtos_base as ent')
            ->whereNull('ent.deleted_at')
            ->select('ent.id', 'ent.descricao_produto', 'ent.sku_base')
            ->orderBy('ent.descricao_produto');
    }
}
