<?php

namespace App\Http\Requests\ProjetoImpressaoParteItem\Concerns;

use App\Services\ProjetoImpressaoParteItem\ProjetoImpressaoParteItemCalculoService;
use App\Services\ProjetoImpressaoParteItem\ProjetoImpressaoParteItemConfig;

trait PreparesProjetoImpressaoParteItemPayload
{
    protected function prepareProjetoImpressaoParteItemPayload(): void
    {
        $calculoService = new ProjetoImpressaoParteItemCalculoService();

        $merge = [
            'usa_suporte'   => $this->resolverBoolean($this->input('usa_suporte'), $this->input('possui_suporte')),
            'usa_brim'      => $this->resolverBoolean($this->input('usa_brim'), $this->input('possui_brim')),
            'usa_engomagem' => $this->resolverBoolean($this->input('usa_engomagem'), $this->input('possui_engomar')),
            'altura_camada'    => $this->input('altura_camada') ?? 0.20,
            'loops_parede'     => $this->input('loops_parede') ?? 2,
            'temperatura_bico' => $this->input('temperatura_bico') ?? ProjetoImpressaoParteItemConfig::TEMPERATURA_BICO_PADRAO,
            'temperatura_mesa' => $this->input('temperatura_mesa') ?? ProjetoImpressaoParteItemConfig::TEMPERATURA_MESA_PADRAO,
        ];

        if ($this->input('distancia_inferior_z') !== null && $this->input('distancia_z_inferior') === null) {
            $merge['distancia_z_inferior'] = $this->input('distancia_inferior_z');
        }

        if ($this->input('quantidade_voltas') !== null && $this->input('quantidade_voltas_suporte') === null) {
            $merge['quantidade_voltas_suporte'] = $this->input('quantidade_voltas');
        }

        if ($this->input('velocidade') !== null && $this->input('velocidade_engomagem') === null) {
            $merge['velocidade_engomagem'] = $this->input('velocidade');
        }

        if ($this->input('fluxo') !== null && $this->input('fluxo_engomagem') === null) {
            $merge['fluxo_engomagem'] = $this->input('fluxo');
        }

        $pesoParte = $this->input('peso_parte');
        try {
            $merge['peso_parte'] = $calculoService->normalizarPeso($pesoParte, 'O peso da parte', true);
        } catch (\Exception) {
            // Mantém valor original para a validação retornar erro amigável.
        }

        $merge['peso_suporte'] = $this->normalizarPesoOpcional($calculoService, $this->input('peso_suporte'), 'O peso de suporte');
        $merge['peso_corado']  = $this->normalizarPesoOpcional($calculoService, $this->input('peso_corado'), 'O peso corado');
        $merge['peso_torre']   = $this->normalizarPesoOpcional($calculoService, $this->input('peso_torre'), 'O peso da torre');

        $this->merge($merge);
    }

    private function resolverBoolean(mixed $principal, mixed $alias): mixed
    {
        if ($principal !== null) {
            return filter_var($principal, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $principal;
        }

        if ($alias !== null) {
            return filter_var($alias, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $alias;
        }

        return null;
    }

    private function normalizarPesoOpcional(
        ProjetoImpressaoParteItemCalculoService $calculoService,
        mixed $valor,
        string $campo
    ): mixed {
        try {
            return $calculoService->normalizarPeso($valor, $campo);
        } catch (\Exception) {
            return $valor;
        }
    }
}
