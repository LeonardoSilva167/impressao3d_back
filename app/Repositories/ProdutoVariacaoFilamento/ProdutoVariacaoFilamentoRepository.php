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

        ProdutoVariacaoFilamento::withTrashed()
            ->whereIn('id_variacao', $idsVariacao)
            ->get()
            ->each(fn (ProdutoVariacaoFilamento $filamento) => $filamento->forceDelete());
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

    /**
     * Conta variações da composição (opcionalmente de uma parte) que possuem filamento vinculado.
     */
    public function countComFilamentoByComposicaoId(int $idComposicao, ?int $idParte = null): int
    {
        $query = DB::table('produto_variacao_filamentos as pvf')
            ->join('produto_variacoes as pv', 'pv.id', '=', 'pvf.id_variacao')
            ->where('pv.id_composicao', $idComposicao)
            ->whereNotNull('pvf.id_filamento')
            ->whereNull('pvf.deleted_at')
            ->whereNull('pv.deleted_at');

        if ($idParte !== null) {
            $query->where('pv.id_parte', $idParte);
        }

        return (int) $query->count();
    }
}
