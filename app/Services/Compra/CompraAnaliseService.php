<?php

namespace App\Services\Compra;

use App\Models\CategoriaItem;
use App\Models\Compra;
use App\Models\PlataformaCompra;
use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CompraAnaliseService
{
    // =========================================================
    // LOOKUPS
    // =========================================================

    public function handleLookupsCompraAnalise(): array
    {
        return [
            'plataformasCompra' => PlataformaCompra::whereNull('deleted_at')
                ->orderBy('descricao')
                ->get(['id', 'descricao']),
            'categoriasItens' => CategoriaItem::whereNull('deleted_at')
                ->orderBy('descricao')
                ->get(['id', 'descricao']),
        ];
    }

    // =========================================================
    // HANDLE FUNCTIONS
    // =========================================================

    public function handleAnaliseCompras(object $atributes): array
    {
        try {
            $atributes = $this->normalizeFiltrosAnalise($atributes);
            $this->validateFiltrosAnalise($atributes);

            return $this->getAnaliseCompras($atributes);
        } catch (Exception $e) {
            throw $e;
        }
    }

    // =========================================================
    // QUERIES
    // =========================================================

    public function getAnaliseCompras(object $filters): array
    {
        $indicadores           = $this->buildIndicadores($filters);
        $resumoPorItem         = $this->buildResumoPorItem($filters);
        $resumoPorCategoria    = $this->buildResumoPorCategoria($filters);
        $resumoPorPlataforma   = $this->buildResumoPorPlataforma($filters);
        $resumoMensal          = $this->buildResumoMensal($filters);
        $rankingItens          = $this->buildRankingItens($filters);
        $indicadoresFormatados = $this->formatIndicadores($indicadores);

        $possuiDados = $this->possuiDadosAnalise(
            $indicadoresFormatados,
            $resumoPorItem,
            $resumoPorCategoria,
            $resumoPorPlataforma,
            $resumoMensal,
            $rankingItens
        );

        $payload = [
            'indicadores'           => $indicadoresFormatados,
            'totais'                => $indicadoresFormatados,
            'resumo_por_item'       => $resumoPorItem,
            'resumo_por_categoria'  => $resumoPorCategoria,
            'resumo_por_plataforma' => $resumoPorPlataforma,
            'resumo_mensal'         => $resumoMensal,
            'ranking_itens'         => $rankingItens,
        ];

        return [
            'status'       => true,
            'possui_dados' => $possuiDados,
            'message'      => $possuiDados
                ? 'Análise carregada com sucesso.'
                : 'Nenhum dado encontrado para os filtros informados.',
            'data'         => $payload,
            ...$payload,
        ];
    }

    // =========================================================
    // HELPERS
    // =========================================================

    private function normalizeFiltrosAnalise(object $atributes): object
    {
        if (empty($atributes->data_inicial) && !empty($atributes->data_inicio)) {
            $atributes->data_inicial = $atributes->data_inicio;
        }

        if (empty($atributes->data_final) && !empty($atributes->data_fim)) {
            $atributes->data_final = $atributes->data_fim;
        }

        $atributes->ids_plataforma_compra = $this->normalizeIdsFilter(
            $atributes,
            ['ids_plataforma_compra', 'id_plataformas_compra', 'id_plataforma_compra']
        );

        $atributes->ids_categoria_item = $this->normalizeIdsFilter(
            $atributes,
            ['ids_categoria_item', 'id_categorias_item', 'id_categoria_item']
        );

        $atributes->ids_item = $this->normalizeIdsFilter(
            $atributes,
            ['ids_item', 'ids_itens', 'id_itens', 'id_item']
        );

        return $atributes;
    }

    /**
     * @param  string[]  $keys
     */
    private function normalizeIdsFilter(object $atributes, array $keys): array
    {
        $values = [];

        foreach ($keys as $key) {
            if (!isset($atributes->{$key}) || $atributes->{$key} === '' || $atributes->{$key} === null) {
                continue;
            }

            $raw = $atributes->{$key};

            if (is_array($raw)) {
                $values = array_merge($values, $raw);
                continue;
            }

            if (is_string($raw) && str_contains($raw, ',')) {
                $values = array_merge($values, explode(',', $raw));
                continue;
            }

            $values[] = $raw;
        }

        $ids = [];

        foreach ($values as $value) {
            if ($value === '' || $value === null) {
                continue;
            }

            $id = (int) $value;

            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    private function validateFiltrosAnalise(object $atributes): void
    {
        $validator = Validator::make((array) $atributes, [
            'data_inicio'               => ['nullable', 'date_format:Y-m-d'],
            'data_fim'                  => ['nullable', 'date_format:Y-m-d'],
            'data_inicial'              => ['nullable', 'date_format:Y-m-d'],
            'data_final'                => ['nullable', 'date_format:Y-m-d', 'after_or_equal:data_inicial'],
            'ids_plataforma_compra'     => ['nullable', 'array'],
            'ids_plataforma_compra.*'   => ['integer', 'exists:plataforma_compras,id'],
            'ids_categoria_item'        => ['nullable', 'array'],
            'ids_categoria_item.*'      => ['integer', 'exists:categorias_itens,id'],
            'ids_item'                  => ['nullable', 'array'],
            'ids_item.*'                => ['integer', 'exists:itens,id'],
        ], [
            'data_inicio.date_format'           => 'A data início deve estar no formato YYYY-MM-DD.',
            'data_fim.date_format'                => 'A data fim deve estar no formato YYYY-MM-DD.',
            'data_inicial.date_format'            => 'A data inicial deve estar no formato YYYY-MM-DD.',
            'data_final.date_format'              => 'A data final deve estar no formato YYYY-MM-DD.',
            'data_final.after_or_equal'           => 'A data final deve ser maior ou igual à data inicial.',
            'ids_plataforma_compra.*.exists'      => 'Plataforma de compra não encontrada.',
            'ids_categoria_item.*.exists'         => 'Categoria de item não encontrada.',
            'ids_item.*.exists'                   => 'Item não encontrado.',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first(), 422);
        }
    }

    private function buildIndicadores(object $filters): object
    {
        if (!$this->baseItensQuery($filters)->exists()) {
            return $this->emptyIndicadores();
        }

        $totalCompras = (float) $this->baseItensQuery($filters)
            ->sum('ci.valor_total');

        $totaisCompra = DB::table('compras as c')
            ->whereIn('c.id', $this->comprasIdsSubquery($filters))
            ->whereNull('c.deleted_at')
            ->where('c.status', Compra::STATUS_ATIVA)
            ->selectRaw('
                COALESCE(SUM(c.valor_frete), 0)    as total_frete,
                COALESCE(SUM(c.valor_imposto), 0)  as total_impostos,
                COALESCE(SUM(c.valor_taxa), 0)     as total_taxas,
                COALESCE(SUM(c.valor_desconto), 0) as total_descontos
            ')
            ->first();

        $totalFrete     = (float) ($totaisCompra->total_frete ?? 0);
        $totalImpostos  = (float) ($totaisCompra->total_impostos ?? 0);
        $totalTaxas     = (float) ($totaisCompra->total_taxas ?? 0);
        $totalDescontos = (float) ($totaisCompra->total_descontos ?? 0);

        return (object) [
            'total_compras'       => round($totalCompras, 2),
            'total_frete'         => round($totalFrete, 2),
            'total_impostos'      => round($totalImpostos, 2),
            'total_taxas'         => round($totalTaxas, 2),
            'total_descontos'     => round($totalDescontos, 2),
            'total_investido'     => round(
                $totalCompras + $totalFrete + $totalImpostos + $totalTaxas - $totalDescontos,
                2
            ),
            'valor_estoque_atual' => round($this->buildValorEstoqueAtual($filters), 2),
        ];
    }

    private function emptyIndicadores(): object
    {
        return (object) [
            'total_compras'       => 0.0,
            'total_frete'         => 0.0,
            'total_impostos'      => 0.0,
            'total_taxas'         => 0.0,
            'total_descontos'     => 0.0,
            'total_investido'     => 0.0,
            'valor_estoque_atual' => 0.0,
        ];
    }

    private function formatIndicadores(object $indicadores): array
    {
        return [
            'total_compras'       => (float) $indicadores->total_compras,
            'total_frete'         => (float) $indicadores->total_frete,
            'total_impostos'      => (float) $indicadores->total_impostos,
            'total_taxas'         => (float) $indicadores->total_taxas,
            'total_descontos'     => (float) $indicadores->total_descontos,
            'total_investido'     => (float) $indicadores->total_investido,
            'valor_estoque_atual' => (float) $indicadores->valor_estoque_atual,
        ];
    }

    private function possuiDadosAnalise(
        array $indicadores,
        array $resumoPorItem,
        array $resumoPorCategoria,
        array $resumoPorPlataforma,
        array $resumoMensal,
        array $rankingItens
    ): bool {
        if (
            $indicadores['total_compras'] > 0
            || $indicadores['total_investido'] > 0
            || $indicadores['valor_estoque_atual'] > 0
        ) {
            return true;
        }

        return !empty($resumoPorItem)
            || !empty($resumoPorCategoria)
            || !empty($resumoPorPlataforma)
            || !empty($resumoMensal)
            || !empty($rankingItens);
    }

    private function buildResumoPorItem(object $filters): array
    {
        return $this->baseItensQuery($filters)
            ->select([
                'ci.id_item',
                'i.descricao as nome_item',
            ])
            ->selectRaw('SUM(ci.qtd_interna) as quantidade_comprada')
            ->selectRaw('SUM(ci.valor_total) as valor_total_comprado')
            ->selectRaw('
                CASE
                    WHEN SUM(ci.qtd_interna) > 0
                    THEN SUM(ci.valor_total) / SUM(ci.qtd_interna)
                    ELSE 0
                END as custo_medio
            ')
            ->groupBy('ci.id_item', 'i.descricao')
            ->orderBy('i.descricao')
            ->get()
            ->map(fn ($row) => [
                'id_item'              => (int) $row->id_item,
                'nome_item'            => $row->nome_item,
                'quantidade_comprada'  => (float) round((float) $row->quantidade_comprada, 4),
                'valor_total_comprado' => (float) round((float) $row->valor_total_comprado, 2),
                'custo_medio'          => (float) round((float) $row->custo_medio, 4),
            ])
            ->all();
    }

    private function buildResumoPorCategoria(object $filters): array
    {
        return $this->baseItensQuery($filters)
            ->selectRaw('COALESCE(cat.descricao, \'Sem categoria\') as categoria')
            ->selectRaw('SUM(ci.valor_total) as valor_total')
            ->groupBy('cat.id', 'cat.descricao')
            ->orderByDesc('valor_total')
            ->get()
            ->map(fn ($row) => [
                'categoria'   => $row->categoria,
                'valor_total' => (float) round((float) $row->valor_total, 2),
            ])
            ->all();
    }

    private function buildResumoPorPlataforma(object $filters): array
    {
        return $this->baseItensQuery($filters)
            ->select([
                'plat.descricao as plataforma',
            ])
            ->selectRaw('SUM(ci.valor_total) as valor_total')
            ->groupBy('plat.id', 'plat.descricao')
            ->orderByDesc('valor_total')
            ->get()
            ->map(fn ($row) => [
                'plataforma'  => $row->plataforma,
                'valor_total' => (float) round((float) $row->valor_total, 2),
            ])
            ->all();
    }

    private function buildResumoMensal(object $filters): array
    {
        return $this->baseItensQuery($filters)
            ->selectRaw('YEAR(c.data_compra) as ano')
            ->selectRaw('MONTH(c.data_compra) as mes')
            ->selectRaw('SUM(ci.valor_total) as valor_total')
            ->groupByRaw('YEAR(c.data_compra), MONTH(c.data_compra)')
            ->orderByRaw('YEAR(c.data_compra), MONTH(c.data_compra)')
            ->get()
            ->map(fn ($row) => [
                'ano'         => (int) $row->ano,
                'mes'         => (int) $row->mes,
                'valor_total' => (float) round((float) $row->valor_total, 2),
            ])
            ->all();
    }

    private function buildRankingItens(object $filters, int $limit = 10): array
    {
        return $this->baseItensQuery($filters)
            ->select([
                'ci.id_item',
                'i.descricao as item',
            ])
            ->selectRaw('SUM(ci.qtd_interna) as quantidade')
            ->selectRaw('SUM(ci.valor_total) as valor_comprado')
            ->groupBy('ci.id_item', 'i.descricao')
            ->orderByDesc('quantidade')
            ->orderByDesc('valor_comprado')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'id_item'        => (int) $row->id_item,
                'item'           => $row->item,
                'quantidade'     => (float) round((float) $row->quantidade, 4),
                'valor_comprado' => (float) round((float) $row->valor_comprado, 2),
            ])
            ->all();
    }

    private function buildValorEstoqueAtual(object $filters): float
    {
        $query = DB::table('itens as i')
            ->whereNull('i.deleted_at');

        if (!empty($filters->ids_item)) {
            $query->whereIn('i.id', $filters->ids_item);
        }

        if (!empty($filters->ids_categoria_item)) {
            $query->whereIn('i.id_categoria_item', $filters->ids_categoria_item);
        }

        $hasCompraScope = !empty($filters->data_inicial)
            || !empty($filters->data_final)
            || !empty($filters->ids_plataforma_compra);

        if ($hasCompraScope) {
            $itensIds = $this->baseItensQuery($filters)
                ->distinct()
                ->pluck('ci.id_item');

            if ($itensIds->isEmpty()) {
                return 0;
            }

            $query->whereIn('i.id', $itensIds);
        }

        $result = $query
            ->selectRaw('COALESCE(SUM(i.estoque_atual * i.preco_medio_atual), 0) as valor_estoque_atual')
            ->first();

        return (float) ($result->valor_estoque_atual ?? 0);
    }

    private function baseItensQuery(object $filters): Builder
    {
        $query = DB::table('compras_itens as ci')
            ->join('compras as c', 'c.id', '=', 'ci.id_compra')
            ->join('itens as i', 'i.id', '=', 'ci.id_item')
            ->join('plataforma_compras as plat', 'plat.id', '=', 'c.id_plataforma_compra')
            ->leftJoin('categorias_itens as cat', 'cat.id', '=', 'i.id_categoria_item')
            ->whereNull('ci.deleted_at')
            ->whereNull('c.deleted_at')
            ->whereNull('i.deleted_at')
            ->whereNull('plat.deleted_at')
            ->where('c.status', Compra::STATUS_ATIVA);

        $this->applyFilters($query, $filters);

        return $query;
    }

    private function comprasIdsSubquery(object $filters): Builder
    {
        return $this->baseItensQuery($filters)
            ->select('c.id')
            ->distinct();
    }

    private function applyFilters(Builder $query, object $filters): void
    {
        if (!empty($filters->data_inicial)) {
            $query->whereDate('c.data_compra', '>=', $filters->data_inicial);
        }

        if (!empty($filters->data_final)) {
            $query->whereDate('c.data_compra', '<=', $filters->data_final);
        }

        if (!empty($filters->ids_plataforma_compra)) {
            $query->whereIn('c.id_plataforma_compra', $filters->ids_plataforma_compra);
        }

        if (!empty($filters->ids_categoria_item)) {
            $query->whereIn('i.id_categoria_item', $filters->ids_categoria_item);
        }

        if (!empty($filters->ids_item)) {
            $query->whereIn('ci.id_item', $filters->ids_item);
        }
    }
}
