<?php

namespace App\Services\ProjetoImpressao;

use App\Repositories\Configuracao\ConfiguracaoRepository;
use App\Services\Custo\CustoCalculoService;
use App\Services\ProjetoImpressaoParteItem\ProjetoImpressaoParteItemCalculoService;
use Illuminate\Support\Facades\DB;

class ProjetoImpressaoCustoService
{
    private CustoCalculoService $_custoService;

    private ConfiguracaoRepository $_configuracaoRepository;

    private ProjetoImpressaoParteItemCalculoService $_itemCalculoService;

    public function __construct()
    {
        $this->_custoService           = new CustoCalculoService();
        $this->_configuracaoRepository = new ConfiguracaoRepository();
        $this->_itemCalculoService     = new ProjetoImpressaoParteItemCalculoService();
    }

    /**
     * @return array{custo_filamento: float, custo_energia: float, custo_desgaste: float, custo_total: float}
     */
    public function calcularCustosExibicao(
        float $pesoGramas,
        ?float $precoMedioGrama,
        string $tempoImpressao,
    ): array {
        $config = $this->_configuracaoRepository->getCustosConfig();

        return $this->_custoService->calcularCustosCompletos(
            $pesoGramas,
            $precoMedioGrama,
            $tempoImpressao,
            $config['custo_energia_kwh'],
            $config['custo_desgaste_hora'],
        );
    }

    /**
     * Recalcula energia, desgaste e total a partir do filamento já calculado e do tempo.
     *
     * @return array{custo_filamento: float, custo_energia: float, custo_desgaste: float, custo_total: float}
     */
    public function calcularCustosExibicaoPorFilamentoETempo(
        float $custoFilamento,
        string $tempoImpressao,
    ): array {
        $config = $this->_configuracaoRepository->getCustosConfig();

        try {
            $horasDecimais = $this->_custoService->tempoParaHorasDecimais($tempoImpressao);
        } catch (\Exception) {
            $horasDecimais = 0.0;
        }

        $custoEnergia  = $this->_custoService->calcularCustoEnergia($horasDecimais, $config['custo_energia_kwh']);
        $custoDesgaste = $this->_custoService->calcularCustoDesgaste($horasDecimais, $config['custo_desgaste_hora']);
        $custoTotal    = $this->_custoService->calcularCustoTotal($custoFilamento, $custoEnergia, $custoDesgaste);

        return $this->_custoService->formatarCustosResposta([
            'custo_filamento' => $custoFilamento,
            'custo_energia'   => $custoEnergia,
            'custo_desgaste'  => $custoDesgaste,
            'custo_total'     => $custoTotal,
        ]);
    }

    /**
     * @return array{custo_filamento: float, custo_energia: float, custo_desgaste: float, custo_total: float}
     */
    public function calcularCustosExibicaoItem(object|array $item): array
    {
        $data = is_array($item) ? $item : (array) $item;

        $pesoTotal = $this->_itemCalculoService->calcularPesoTotal(
            (float) ($data['peso_parte'] ?? 0),
            (float) ($data['peso_suporte'] ?? 0),
            (float) ($data['peso_corado'] ?? 0),
            (float) ($data['peso_torre'] ?? 0),
        );

        $precoMedio = isset($data['preco_medio_grama']) && $data['preco_medio_grama'] !== null
            ? (float) $data['preco_medio_grama']
            : null;

        return $this->calcularCustosExibicao(
            $pesoTotal,
            $precoMedio,
            (string) ($data['tempo_impressao'] ?? '00:00'),
        );
    }

    /**
     * @return array{custo_filamento: float, custo_energia: float, custo_desgaste: float, custo_total: float}
     */
    public function calcularCustosExibicaoVariacao(object|array $variacao): array
    {
        $data = is_array($variacao) ? $variacao : (array) $variacao;

        $peso = isset($data['peso_item']) && $data['peso_item'] !== null
            ? (float) $data['peso_item']
            : 0.0;

        $precoMedio = isset($data['preco_medio_grama']) && $data['preco_medio_grama'] !== null
            ? (float) $data['preco_medio_grama']
            : null;

        return $this->calcularCustosExibicao(
            $peso,
            $precoMedio,
            (string) ($data['tempo_impressao'] ?? '00:00'),
        );
    }

    /**
     * @param  array<int, int|string>  $idsPartes
     * @return array<int, array{custo_filamento: float, custo_energia: float, custo_desgaste: float, custo_total: float}>
     */
    public function calcularCustosExibicaoPorPartes(array $idsPartes): array
    {
        if (empty($idsPartes)) {
            return [];
        }

        $itens = DB::table('projetos_impressao_parte_itens')
            ->select(
                'id',
                'id_projeto_impressao_parte',
                'peso_parte',
                'peso_suporte',
                'peso_corado',
                'peso_torre',
                'tempo_impressao',
            )
            ->whereIn('id_projeto_impressao_parte', $idsPartes)
            ->whereNull('deleted_at')
            ->get();

        $custosPorParte = [];

        foreach ($itens as $item) {
            $idParte = (int) $item->id_projeto_impressao_parte;
            $custosItem = $this->calcularCustosExibicaoItem((array) $item);

            if (!isset($custosPorParte[$idParte])) {
                $custosPorParte[$idParte] = [
                    'custo_filamento' => 0.0,
                    'custo_energia'   => 0.0,
                    'custo_desgaste'  => 0.0,
                    'custo_total'     => 0.0,
                ];
            }

            foreach (['custo_filamento', 'custo_energia', 'custo_desgaste', 'custo_total'] as $campo) {
                $custosPorParte[$idParte][$campo] += $custosItem[$campo];
            }
        }

        foreach ($custosPorParte as $idParte => $custos) {
            $custosPorParte[$idParte] = $this->_custoService->formatarCustosResposta($custos);
        }

        return $custosPorParte;
    }

    /**
     * @param  array<int, int|string>  $idsProjetos
     * @return array<int, array{custo_filamento: float, custo_energia: float, custo_desgaste: float, custo_total: float}>
     */
    public function calcularCustosExibicaoPorProjetos(array $idsProjetos): array
    {
        if (empty($idsProjetos)) {
            return [];
        }

        $partes = DB::table('projetos_impressao_partes')
            ->select('id', 'id_projeto_impressao')
            ->whereIn('id_projeto_impressao', $idsProjetos)
            ->whereNull('deleted_at')
            ->get();

        $idsPartes = $partes->pluck('id')->map(fn ($id) => (int) $id)->toArray();
        $custosPorParte = $this->calcularCustosExibicaoPorPartes($idsPartes);

        $custosPorProjeto = [];

        foreach ($partes as $parte) {
            $idProjeto = (int) $parte->id_projeto_impressao;
            $idParte   = (int) $parte->id;
            $custosParte = $custosPorParte[$idParte] ?? [
                'custo_filamento' => 0.0,
                'custo_energia'   => 0.0,
                'custo_desgaste'  => 0.0,
                'custo_total'     => 0.0,
            ];

            if (!isset($custosPorProjeto[$idProjeto])) {
                $custosPorProjeto[$idProjeto] = [
                    'custo_filamento' => 0.0,
                    'custo_energia'   => 0.0,
                    'custo_desgaste'  => 0.0,
                    'custo_total'     => 0.0,
                ];
            }

            foreach (['custo_filamento', 'custo_energia', 'custo_desgaste', 'custo_total'] as $campo) {
                $custosPorProjeto[$idProjeto][$campo] += $custosParte[$campo];
            }
        }

        foreach ($custosPorProjeto as $idProjeto => $custos) {
            $custosPorProjeto[$idProjeto] = $this->_custoService->formatarCustosResposta($custos);
        }

        return $custosPorProjeto;
    }

    /**
     * @return array{custo_filamento: float, custo_energia: float, custo_desgaste: float, custo_total: float}
     */
    public function calcularCustosExibicaoGradeProduto(float $custoFilamento, string $tempoTotal): array
    {
        return $this->calcularCustosExibicaoPorFilamentoETempo($custoFilamento, $tempoTotal);
    }

    /**
     * @return array{custo_filamento: float, custo_energia: float, custo_desgaste: float, custo_total: float}
     */
    public function formatarCustosResposta(?object $record): array
    {
        if ($record === null) {
            return $this->_custoService->formatarCustosResposta([]);
        }

        return $this->_custoService->formatarCustosResposta((array) $record);
    }

    public function appendCustosExibicaoItem(array $data): array
    {
        $custos = $this->calcularCustosExibicaoItem($data);

        return array_merge($data, $custos);
    }

    public function appendCustosExibicaoGrade(array $data): array
    {
        $custos = $this->calcularCustosExibicaoGradeProduto(
            (float) ($data['custo_filamento'] ?? 0),
            (string) ($data['tempo_total'] ?? '00:00'),
        );

        return array_merge($data, $custos);
    }

    public function appendCustosExibicaoRegistro(array $data, int $idParte): array
    {
        $custosPorParte = $this->calcularCustosExibicaoPorPartes([$idParte]);
        $custos = $custosPorParte[$idParte] ?? $this->formatarCustosResposta(null);

        return array_merge($data, $custos);
    }

    public function appendCustosExibicaoProjeto(array $data, int $idProjeto): array
    {
        $custosPorProjeto = $this->calcularCustosExibicaoPorProjetos([$idProjeto]);
        $custos = $custosPorProjeto[$idProjeto] ?? $this->formatarCustosResposta(null);

        return array_merge($data, $custos);
    }

    /** @deprecated Custos não são mais persistidos; mantido para compatibilidade de chamadas. */
    public function recalcularCustosItem(int $idItem): void
    {
        // Custos calculados em tempo real na exibição.
    }

    /** @deprecated Custos não são mais persistidos; mantido para compatibilidade de chamadas. */
    public function recalcularCustosParte(int $idParte): void
    {
        // Custos calculados em tempo real na exibição.
    }

    /** @deprecated Custos não são mais persistidos; mantido para compatibilidade de chamadas. */
    public function recalcularCustosProjeto(int $idProjeto): void
    {
        // Custos calculados em tempo real na exibição.
    }

    /** @deprecated Custos não são mais persistidos; mantido para compatibilidade de chamadas. */
    public function recalcularCustosProjetoCompleto(int $idProjeto): void
    {
        // Custos calculados em tempo real na exibição.
    }
}
