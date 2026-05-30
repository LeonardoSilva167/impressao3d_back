<?php

namespace App\Repositories\ProdutoComposicaoItem;

use App\Models\ProdutoComposicaoItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProdutoComposicaoItemRepository
{
    public function create(array $data): ProdutoComposicaoItem
    {
        return ProdutoComposicaoItem::create($data);
    }

    public function deleteByVariacaoIds(array $idsVariacao): void
    {
        if (empty($idsVariacao)) {
            return;
        }

        ProdutoComposicaoItem::whereIn('id_produto_composicao_variacao', $idsVariacao)->delete();
    }

    public function getByVariacaoId(int $idVariacao): Collection
    {
        return DB::table('produto_composicao_itens as pci')
            ->select(
                'pci.id',
                'pci.id_produto_composicao_variacao',
                'pci.id_item_projeto',
                'pci.id_filamento',
                'pci.peso_total',
                'pci.tempo_impressao',
                'pci.preco_medio_grama',
                'pci.custo_item',
                'pci.created_at',
                'pci.updated_at',
                'item.nome_item',
                'parte.nome_parte',
                'fil.resumo as filamento_resumo',
                'cor.descricao as cor_descricao',
                'cor.hexadecimal as cor_hexadecimal',
            )
            ->join('projetos_impressao_parte_itens as item', 'item.id', '=', 'pci.id_item_projeto')
            ->join('projetos_impressao_partes as parte', 'parte.id', '=', 'item.id_projeto_impressao_parte')
            ->join('filamentos as fil', 'fil.id', '=', 'pci.id_filamento')
            ->leftJoin('cores as cor', 'cor.id', '=', 'item.id_cor')
            ->where('pci.id_produto_composicao_variacao', $idVariacao)
            ->whereNull('item.deleted_at')
            ->whereNull('parte.deleted_at')
            ->whereNull('fil.deleted_at')
            ->orderBy('parte.nome_parte')
            ->orderBy('item.nome_item')
            ->get();
    }
}
