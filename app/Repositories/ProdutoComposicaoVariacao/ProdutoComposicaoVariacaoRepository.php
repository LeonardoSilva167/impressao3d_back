<?php

namespace App\Repositories\ProdutoComposicaoVariacao;

use App\Models\ProdutoComposicaoVariacao;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProdutoComposicaoVariacaoRepository
{
    public function create(array $data): ProdutoComposicaoVariacao
    {
        return ProdutoComposicaoVariacao::create($data);
    }

    public function deleteByComposicaoId(int $idComposicao): void
    {
        ProdutoComposicaoVariacao::where('id_produto_composicao', $idComposicao)->delete();
    }

    public function getByComposicaoId(int $idComposicao): Collection
    {
        return DB::table('produto_composicao_variacoes as pcv')
            ->select(
                'pcv.id',
                'pcv.id_produto_composicao',
                'pcv.id_produto_variacao',
                'pcv.custo_total_filamentos',
                'pcv.tempo_total_impressao',
                'pcv.created_at',
                'pcv.updated_at',
                'pv.sku',
                'pv.status',
                'pv.id_cor_primaria',
                'pv.id_cor_secundaria',
                'pv.id_cor_terciaria',
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
            ->join('produto_variacoes as pv', 'pv.id', '=', 'pcv.id_produto_variacao')
            ->join('cores as cp', 'cp.id', '=', 'pv.id_cor_primaria')
            ->leftJoin('cores as cs', 'cs.id', '=', 'pv.id_cor_secundaria')
            ->leftJoin('cores as ct', 'ct.id', '=', 'pv.id_cor_terciaria')
            ->where('pcv.id_produto_composicao', $idComposicao)
            ->whereNull('pv.deleted_at')
            ->orderBy('pv.sku')
            ->get();
    }
}
