<?php

namespace App\Repositories\ProdutoComposicaoCor;

use App\Models\ProdutoComposicaoCor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProdutoComposicaoCorRepository
{
    public function create(array $data): ProdutoComposicaoCor
    {
        return ProdutoComposicaoCor::create($data);
    }

    public function deleteByComposicaoId(int $idComposicao): void
    {
        ProdutoComposicaoCor::where('id_composicao', $idComposicao)
            ->whereNull('deleted_at')
            ->get()
            ->each(fn (ProdutoComposicaoCor $cor) => $cor->delete());
    }

    public function deleteByParteId(int $idComposicao, int $idParte): void
    {
        ProdutoComposicaoCor::where('id_composicao', $idComposicao)
            ->where('id_parte', $idParte)
            ->whereNull('deleted_at')
            ->get()
            ->each(fn (ProdutoComposicaoCor $cor) => $cor->delete());
    }

    public function deleteByItemProjetoIds(int $idComposicao, array $idsItemProjeto): void
    {
        if (empty($idsItemProjeto)) {
            return;
        }

        ProdutoComposicaoCor::where('id_composicao', $idComposicao)
            ->whereIn('id_item_projeto', $idsItemProjeto)
            ->whereNull('deleted_at')
            ->get()
            ->each(fn (ProdutoComposicaoCor $cor) => $cor->delete());
    }

    public function getByComposicaoId(int $idComposicao, ?int $idParte = null): Collection
    {
        $query = DB::table('produto_composicao_cores as pcc')
            ->select(
                'pcc.id',
                'pcc.id_composicao',
                'pcc.id_parte',
                'pcc.id_item_projeto',
                'pcc.tipo_cor',
                'pcc.id_cor',
                'pcc.created_at',
                'parte.nome_parte',
                'item.nome_item',
                'cor.descricao as cor_descricao',
                'cor.codigo as cor_codigo',
                'cor.hexadecimal as cor_hexadecimal',
            )
            ->join('projetos_impressao_parte_itens as item', 'item.id', '=', 'pcc.id_item_projeto')
            ->join('projetos_impressao_partes as parte', 'parte.id', '=', 'pcc.id_parte')
            ->join('cores as cor', 'cor.id', '=', 'pcc.id_cor')
            ->where('pcc.id_composicao', $idComposicao)
            ->whereNull('pcc.deleted_at')
            ->whereNull('item.deleted_at')
            ->whereNull('parte.deleted_at')
            ->whereNull('cor.deleted_at');

        if ($idParte !== null) {
            $query->where('pcc.id_parte', $idParte);
        }

        return $query
            ->orderBy('parte.nome_parte')
            ->orderBy('item.nome_item')
            ->orderBy('pcc.tipo_cor')
            ->orderBy('cor.descricao')
            ->get();
    }

    public function getByParteId(int $idComposicao, int $idParte): Collection
    {
        return $this->getByComposicaoId($idComposicao, $idParte);
    }

    public function countItensComCorByParte(int $idComposicao, int $idParte): int
    {
        return DB::table('produto_composicao_cores')
            ->where('id_composicao', $idComposicao)
            ->where('id_parte', $idParte)
            ->whereNull('deleted_at')
            ->distinct('id_item_projeto')
            ->count('id_item_projeto');
    }

    public function countItensDistintosByComposicao(int $idComposicao): int
    {
        return DB::table('produto_composicao_cores')
            ->where('id_composicao', $idComposicao)
            ->whereNull('deleted_at')
            ->distinct('id_item_projeto')
            ->count('id_item_projeto');
    }

    public function partePossuiCores(int $idComposicao, int $idParte): bool
    {
        return ProdutoComposicaoCor::where('id_composicao', $idComposicao)
            ->where('id_parte', $idParte)
            ->whereNull('deleted_at')
            ->exists();
    }
}
