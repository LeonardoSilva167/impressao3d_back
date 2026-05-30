<?php

namespace App\Services\ProjetoImpressao;

use App\Models\ProjetoImpressao;
use App\Models\ProjetoImpressaoParte;
use App\Models\ProjetoImpressaoParteItem;
use App\Repositories\Configuracao\ConfiguracaoRepository;
use App\Services\Custo\CustoCalculoService;
use App\Services\ProjetoImpressaoParteItem\ProjetoImpressaoParteItemCalculoService;

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
    public function calcularCustosItem(object $item): array
    {
        $config = $this->_configuracaoRepository->getCustosConfig();

        $pesoTotal = $this->_itemCalculoService->calcularPesoTotal(
            (float) ($item->peso_parte ?? 0),
            (float) ($item->peso_suporte ?? 0),
            (float) ($item->peso_corado ?? 0),
            (float) ($item->peso_torre ?? 0),
        );

        return $this->_custoService->calcularCustosCompletos(
            $pesoTotal,
            null,
            (string) ($item->tempo_impressao ?? '00:00'),
            $config['custo_energia_kwh'],
            $config['custo_desgaste_hora'],
        );
    }

    public function recalcularCustosItem(int $idItem): void
    {
        $item = ProjetoImpressaoParteItem::where('id', $idItem)
            ->whereNull('deleted_at')
            ->first();

        if (!$item) {
            return;
        }

        $custos = $this->calcularCustosItem($item);

        $item->update($custos);
        $this->recalcularCustosParte((int) $item->id_projeto_impressao_parte);
    }

    public function recalcularCustosParte(int $idParte): void
    {
        $itens = ProjetoImpressaoParteItem::where('id_projeto_impressao_parte', $idParte)
            ->whereNull('deleted_at')
            ->get(['custo_filamento', 'custo_energia', 'custo_desgaste', 'custo_total']);

        $custos = $this->_custoService->somarCustos($itens->toArray());

        $parte = ProjetoImpressaoParte::where('id', $idParte)
            ->whereNull('deleted_at')
            ->first();

        if (!$parte) {
            return;
        }

        $parte->update($custos);
        $this->recalcularCustosProjeto((int) $parte->id_projeto_impressao);
    }

    public function recalcularCustosProjeto(int $idProjeto): void
    {
        $partes = ProjetoImpressaoParte::where('id_projeto_impressao', $idProjeto)
            ->whereNull('deleted_at')
            ->get(['custo_filamento', 'custo_energia', 'custo_desgaste', 'custo_total']);

        $custos = $this->_custoService->somarCustos($partes->toArray());

        ProjetoImpressao::where('id', $idProjeto)
            ->whereNull('deleted_at')
            ->update($custos);
    }

    public function recalcularCustosProjetoCompleto(int $idProjeto): void
    {
        $partes = ProjetoImpressaoParte::where('id_projeto_impressao', $idProjeto)
            ->whereNull('deleted_at')
            ->pluck('id');

        foreach ($partes as $idParte) {
            $itens = ProjetoImpressaoParteItem::where('id_projeto_impressao_parte', $idParte)
                ->whereNull('deleted_at')
                ->get();

            foreach ($itens as $item) {
                $item->update($this->calcularCustosItem($item));
            }

            $this->recalcularCustosParte((int) $idParte);
        }
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

    public function appendCustosResposta(array $data, ?object $record = null): array
    {
        $custos = $record !== null
            ? $this->formatarCustosResposta($record)
            : $this->formatarCustosResposta((object) $data);

        return array_merge($data, $custos);
    }
}
