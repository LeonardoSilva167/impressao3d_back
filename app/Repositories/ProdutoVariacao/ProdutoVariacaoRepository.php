<?php

namespace App\Repositories\ProdutoVariacao;

use App\Models\ProdutoVariacao;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProdutoVariacaoRepository
{
    public function create(array $data): ProdutoVariacao
    {
        return ProdutoVariacao::create($data);
    }

    public function findById(int|string $id): ?ProdutoVariacao
    {
        return ProdutoVariacao::where('id', $id)->whereNull('deleted_at')->first();
    }

    public function deleteByComposicaoId(int $idComposicao): void
    {
        ProdutoVariacao::where('id_composicao', $idComposicao)
            ->whereNull('deleted_at')
            ->get()
            ->each(fn (ProdutoVariacao $variacao) => $variacao->delete());
    }

    public function deleteByParteId(int $idComposicao, int $idParte): void
    {
        ProdutoVariacao::withTrashed()
            ->where('id_composicao', $idComposicao)
            ->where('id_parte', $idParte)
            ->get()
            ->each(fn (ProdutoVariacao $variacao) => $variacao->forceDelete());
    }

    public function softDeleteByProdutoId(int $idProduto): void
    {
        $idsComposicao = DB::table('produto_composicoes')
            ->where('id_produto', $idProduto)
            ->whereNull('deleted_at')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        if (empty($idsComposicao)) {
            return;
        }

        ProdutoVariacao::whereIn('id_composicao', $idsComposicao)
            ->whereNull('deleted_at')
            ->get()
            ->each(fn (ProdutoVariacao $variacao) => $variacao->delete());
    }

    public function getByComposicaoId(int $idComposicao, ?int $idParte = null): Collection
    {
        $query = DB::table('produto_variacoes as pv')
            ->select(
                'pv.id',
                'pv.id_composicao',
                'pv.id_parte',
                'pv.id_item_projeto',
                'pv.tipo_cor',
                'pv.id_cor',
                'pv.id_composicao_cor',
                'pv.created_at',
                'pv.updated_at',
                'parte.nome_parte',
                'item.nome_item',
                'cor.descricao as cor_descricao',
                'cor.codigo as cor_codigo',
                'cor.hexadecimal as cor_hexadecimal',
                'pvf.id as id_filamento_vinculo',
                'pvf.id_filamento',
                'pvf.preco_medio_grama',
                'pvf.peso_item',
                'pvf.custo_filamento',
                'pvf.custo_energia',
                'pvf.custo_desgaste',
                'pvf.custo_total',
                'item.tempo_impressao',
                'fil.resumo as filamento_resumo',
            )
            ->join('projetos_impressao_parte_itens as item', 'item.id', '=', 'pv.id_item_projeto')
            ->join('projetos_impressao_partes as parte', 'parte.id', '=', 'pv.id_parte')
            ->join('cores as cor', 'cor.id', '=', 'pv.id_cor')
            ->leftJoin('produto_variacao_filamentos as pvf', function ($join) {
                $join->on('pvf.id_variacao', '=', 'pv.id')
                    ->whereNull('pvf.deleted_at');
            })
            ->leftJoin('filamentos as fil', function ($join) {
                $join->on('fil.id', '=', 'pvf.id_filamento')
                    ->whereNull('fil.deleted_at');
            })
            ->where('pv.id_composicao', $idComposicao)
            ->whereNull('pv.deleted_at')
            ->whereNull('item.deleted_at')
            ->whereNull('parte.deleted_at')
            ->whereNull('cor.deleted_at');

        if ($idParte !== null) {
            $query->where('pv.id_parte', $idParte);
        }

        return $query
            ->orderBy('parte.nome_parte')
            ->orderBy('item.nome_item')
            ->orderBy('pv.tipo_cor')
            ->orderBy('cor.descricao')
            ->get();
    }

    public function countByComposicaoId(int $idComposicao, ?int $idParte = null): int
    {
        $query = ProdutoVariacao::where('id_composicao', $idComposicao)
            ->whereNull('deleted_at');

        if ($idParte !== null) {
            $query->where('id_parte', $idParte);
        }

        return $query->count();
    }

    public function partePossuiVariacoes(int $idComposicao, int $idParte): bool
    {
        return ProdutoVariacao::where('id_composicao', $idComposicao)
            ->where('id_parte', $idParte)
            ->whereNull('deleted_at')
            ->exists();
    }
}
