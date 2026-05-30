<?php

namespace App\Repositories\ProdutoVariacao;

use App\Models\ProdutoVariacao;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProdutoVariacaoRepository
{
    public function findById(int|string $id): ?ProdutoVariacao
    {
        return ProdutoVariacao::where('id', $id)->whereNull('deleted_at')->first();
    }

    public function findBySku(string $sku, int|string|null $excludeId = null): ?ProdutoVariacao
    {
        $query = ProdutoVariacao::where('sku', $sku)->whereNull('deleted_at');

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->first();
    }

    public function findByCombinacao(
        int $idProdutoBase,
        int $idCorPrimaria,
        ?int $idCorSecundaria,
        ?int $idCorTerciaria,
        bool $incluirDeletadas = false
    ): ?ProdutoVariacao {
        $query = ProdutoVariacao::where('id_produto_base', $idProdutoBase)
            ->where('id_cor_primaria', $idCorPrimaria);

        if ($idCorSecundaria === null) {
            $query->whereNull('id_cor_secundaria');
        } else {
            $query->where('id_cor_secundaria', $idCorSecundaria);
        }

        if ($idCorTerciaria === null) {
            $query->whereNull('id_cor_terciaria');
        } else {
            $query->where('id_cor_terciaria', $idCorTerciaria);
        }

        if (!$incluirDeletadas) {
            $query->whereNull('deleted_at');
        }

        return $query->first();
    }

    public function create(array $data): ProdutoVariacao
    {
        return ProdutoVariacao::create($data);
    }

    public function update(ProdutoVariacao $record, array $data): bool
    {
        return $record->update($data);
    }

    public function delete(ProdutoVariacao $record): bool
    {
        return (bool) $record->delete();
    }

    public function getCodigosCores(?int $idPrimaria, ?int $idSecundaria, ?int $idTerciaria): ?object
    {
        if ($idPrimaria === null) {
            return null;
        }

        $codigoPrimaria = $this->getCodigoCor($idPrimaria);

        if ($codigoPrimaria === null) {
            return null;
        }

        $codigoSecundaria = $idSecundaria !== null ? $this->getCodigoCor($idSecundaria) : null;
        $codigoTerciaria  = $idTerciaria !== null ? $this->getCodigoCor($idTerciaria) : null;

        if ($idSecundaria !== null && $codigoSecundaria === null) {
            return null;
        }

        if ($idTerciaria !== null && $codigoTerciaria === null) {
            return null;
        }

        return (object) [
            'codigo_primaria'   => $codigoPrimaria,
            'codigo_secundaria' => $codigoSecundaria,
            'codigo_terciaria'  => $codigoTerciaria,
        ];
    }

    public function getByProdutoBaseId(int $idProdutoBase, bool $incluirDeletadas = false): Collection
    {
        $query = ProdutoVariacao::where('id_produto_base', $idProdutoBase);

        if (!$incluirDeletadas) {
            $query->whereNull('deleted_at');
        }

        return $query->get();
    }

    public function softDeleteByProdutoBaseId(int $idProdutoBase): void
    {
        ProdutoVariacao::where('id_produto_base', $idProdutoBase)
            ->whereNull('deleted_at')
            ->get()
            ->each(fn (ProdutoVariacao $variacao) => $variacao->delete());
    }

    public function getPaginateQuery(): Builder
    {
        return DB::query()
            ->select(
                'pv.id',
                'pv.id_produto_base',
                'pv.id_cor_primaria',
                'pv.id_cor_secundaria',
                'pv.id_cor_terciaria',
                'pv.sku',
                'pv.status',
                'pv.created_at',
                'pb.descricao_produto',
                'pb.sku_base',
                'cp.descricao as cor_primaria_descricao',
                'cp.codigo as cor_primaria_codigo',
                'cs.descricao as cor_secundaria_descricao',
                'cs.codigo as cor_secundaria_codigo',
                'ct.descricao as cor_terciaria_descricao',
                'ct.codigo as cor_terciaria_codigo',
            )
            ->from('produto_variacoes as pv')
            ->join('produtos_base as pb', 'pb.id', '=', 'pv.id_produto_base')
            ->join('cores as cp', 'cp.id', '=', 'pv.id_cor_primaria')
            ->leftJoin('cores as cs', 'cs.id', '=', 'pv.id_cor_secundaria')
            ->leftJoin('cores as ct', 'ct.id', '=', 'pv.id_cor_terciaria')
            ->whereNull('pv.deleted_at')
            ->whereNull('pb.deleted_at')
            ->whereNull('cp.deleted_at')
            ->where('pv.status', ProdutoVariacao::STATUS_ATIVA)
            ->where(function ($q) {
                $q->whereNull('pv.id_cor_secundaria')
                    ->orWhereNull('cs.deleted_at');
            })
            ->where(function ($q) {
                $q->whereNull('pv.id_cor_terciaria')
                    ->orWhereNull('ct.deleted_at');
            })
            ->orderBy('pv.sku');
    }

    public function findByIdWithRelations(int|string $id): ?object
    {
        return DB::table('produto_variacoes as pv')
            ->select(
                'pv.id',
                'pv.id_produto_base',
                'pv.id_cor_primaria',
                'pv.id_cor_secundaria',
                'pv.id_cor_terciaria',
                'pv.sku',
                'pv.status',
                'pv.created_at',
                'pb.descricao_produto',
                'pb.sku_base',
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
            ->join('produtos_base as pb', 'pb.id', '=', 'pv.id_produto_base')
            ->join('cores as cp', 'cp.id', '=', 'pv.id_cor_primaria')
            ->leftJoin('cores as cs', 'cs.id', '=', 'pv.id_cor_secundaria')
            ->leftJoin('cores as ct', 'ct.id', '=', 'pv.id_cor_terciaria')
            ->whereNull('pv.deleted_at')
            ->whereNull('pb.deleted_at')
            ->whereNull('cp.deleted_at')
            ->where(function ($q) {
                $q->whereNull('pv.id_cor_secundaria')
                    ->orWhereNull('cs.deleted_at');
            })
            ->where(function ($q) {
                $q->whereNull('pv.id_cor_terciaria')
                    ->orWhereNull('ct.deleted_at');
            })
            ->where('pv.id', $id)
            ->first();
    }

    public function getAsyncQuery(): Builder
    {
        return DB::table('produto_variacoes as pv')
            ->whereNull('pv.deleted_at')
            ->where('pv.status', ProdutoVariacao::STATUS_ATIVA)
            ->select('pv.id', 'pv.sku')
            ->orderBy('pv.sku');
    }

    private function getCodigoCor(int $idCor): ?string
    {
        return DB::table('cores')
            ->where('id', $idCor)
            ->whereNull('deleted_at')
            ->value('codigo');
    }
}
