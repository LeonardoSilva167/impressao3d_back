<?php

namespace App\Repositories\ProdutoVariacaoFilamento;

use App\Models\ProdutoVariacaoFilamento;
use Illuminate\Support\Facades\DB;

class ProdutoVariacaoFilamentoRepository
{
    public function create(array $data): ProdutoVariacaoFilamento
    {
        return ProdutoVariacaoFilamento::create($data);
    }

    public function deleteByVariacaoIds(array $idsVariacao): void
    {
        if (empty($idsVariacao)) {
            return;
        }

        ProdutoVariacaoFilamento::whereIn('id_variacao', $idsVariacao)
            ->whereNull('deleted_at')
            ->get()
            ->each(fn (ProdutoVariacaoFilamento $filamento) => $filamento->delete());
    }

    public function deleteByComposicaoId(int $idComposicao): void
    {
        $idsVariacao = DB::table('produto_variacoes')
            ->where('id_composicao', $idComposicao)
            ->whereNull('deleted_at')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        $this->deleteByVariacaoIds($idsVariacao);
    }

    public function deleteByParteId(int $idComposicao, int $idParte): void
    {
        $idsVariacao = DB::table('produto_variacoes')
            ->where('id_composicao', $idComposicao)
            ->where('id_parte', $idParte)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        if (empty($idsVariacao)) {
            return;
        }

        ProdutoVariacaoFilamento::withTrashed()
            ->whereIn('id_variacao', $idsVariacao)
            ->get()
            ->each(fn (ProdutoVariacaoFilamento $filamento) => $filamento->forceDelete());
    }
}
