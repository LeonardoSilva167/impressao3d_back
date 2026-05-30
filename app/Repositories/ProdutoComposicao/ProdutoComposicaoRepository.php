<?php

namespace App\Repositories\ProdutoComposicao;

use App\Models\ProdutoComposicao;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ProdutoComposicaoRepository
{
    public function findById(int|string $id): ?ProdutoComposicao
    {
        return ProdutoComposicao::where('id', $id)->first();
    }

    public function findAtivaByProdutoId(int $idProduto, int|string|null $excludeId = null): ?ProdutoComposicao
    {
        $query = ProdutoComposicao::where('id_produto', $idProduto)
            ->whereNull('deleted_at');

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->first();
    }

    public function create(array $data): ProdutoComposicao
    {
        return ProdutoComposicao::create($data);
    }

    public function update(ProdutoComposicao $record, array $data): bool
    {
        return $record->update($data);
    }

    public function delete(ProdutoComposicao $record): bool
    {
        return (bool) $record->delete();
    }

    public function softDeleteByProdutoId(int $idProduto): void
    {
        ProdutoComposicao::where('id_produto', $idProduto)
            ->whereNull('deleted_at')
            ->get()
            ->each(fn (ProdutoComposicao $composicao) => $composicao->delete());
    }

    public function getPaginateQuery(): Builder
    {
        return DB::query()
            ->select(
                'ent.id',
                'ent.id_produto',
                'ent.id_projeto_impressao',
                'ent.created_at',
                'pb.descricao_produto',
                'pb.sku_base',
                'pi.nome_original_projeto',
                'pi.codigo_projeto',
                DB::raw('(SELECT COUNT(*) FROM produto_variacoes pv WHERE pv.id_composicao = ent.id AND pv.deleted_at IS NULL) as quantidade_variacoes'),
            )
            ->from('produto_composicoes as ent')
            ->join('produtos_base as pb', 'pb.id', '=', 'ent.id_produto')
            ->join('projetos_impressao as pi', 'pi.id', '=', 'ent.id_projeto_impressao')
            ->whereNull('ent.deleted_at')
            ->whereNull('pb.deleted_at')
            ->whereNull('pi.deleted_at')
            ->orderByDesc('ent.created_at');
    }

    public function findByIdWithRelations(int|string $id): ?object
    {
        return DB::table('produto_composicoes as ent')
            ->select(
                'ent.id',
                'ent.id_produto',
                'ent.id_projeto_impressao',
                'ent.created_at',
                'ent.updated_at',
                'pb.descricao_produto',
                'pb.sku_base',
                'pb.codigo_base',
                'pi.nome_original_projeto',
                'pi.codigo_projeto',
                'pi.descricao_projeto',
            )
            ->join('produtos_base as pb', 'pb.id', '=', 'ent.id_produto')
            ->join('projetos_impressao as pi', 'pi.id', '=', 'ent.id_projeto_impressao')
            ->whereNull('ent.deleted_at')
            ->whereNull('pb.deleted_at')
            ->whereNull('pi.deleted_at')
            ->where('ent.id', $id)
            ->first();
    }

    public function getAsyncQuery(): Builder
    {
        return DB::table('produto_composicoes as ent')
            ->join('produtos_base as pb', 'pb.id', '=', 'ent.id_produto')
            ->join('projetos_impressao as pi', 'pi.id', '=', 'ent.id_projeto_impressao')
            ->whereNull('ent.deleted_at')
            ->whereNull('pb.deleted_at')
            ->whereNull('pi.deleted_at')
            ->select(
                'ent.id',
                'pb.descricao_produto',
                'pb.sku_base',
                'pi.nome_original_projeto',
                'pi.codigo_projeto',
            )
            ->orderBy('pb.descricao_produto');
    }
}
