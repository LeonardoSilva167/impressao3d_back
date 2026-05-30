<?php

namespace App\Services\Custo;

use Exception;

class CustoCalculoService
{
    public function tempoParaHorasDecimais(string $tempo): float
    {
        if (!preg_match('/^\d{1,2}:\d{2}$/', $tempo)) {
            throw new Exception('Tempo de impressão inválido: ' . $tempo, 422);
        }

        [$horas, $minutos] = array_map('intval', explode(':', $tempo));

        if ($minutos > 59) {
            throw new Exception('Tempo de impressão inválido: ' . $tempo, 422);
        }

        return round($horas + ($minutos / 60), 4);
    }

    public function calcularCustoFilamento(float $pesoGramas, ?float $precoMedioGrama): float
    {
        if ($precoMedioGrama === null || $precoMedioGrama <= 0 || $pesoGramas <= 0) {
            return 0.0;
        }

        return round($pesoGramas * $precoMedioGrama, 4);
    }

    public function calcularCustoEnergia(float $horasDecimais, float $custoEnergiaKwh): float
    {
        if ($horasDecimais <= 0) {
            return 0.0;
        }

        return round($horasDecimais * $custoEnergiaKwh, 4);
    }

    public function calcularCustoDesgaste(float $horasDecimais, float $custoDesgasteHora): float
    {
        if ($horasDecimais <= 0) {
            return 0.0;
        }

        return round($horasDecimais * $custoDesgasteHora, 4);
    }

    public function calcularCustoTotal(float $custoFilamento, float $custoEnergia, float $custoDesgaste): float
    {
        return round($custoFilamento + $custoEnergia + $custoDesgaste, 4);
    }

    /**
     * @return array{custo_filamento: float, custo_energia: float, custo_desgaste: float, custo_total: float}
     */
    public function calcularCustosCompletos(
        float $pesoGramas,
        ?float $precoMedioGrama,
        string $tempoImpressao,
        float $custoEnergiaKwh,
        float $custoDesgasteHora,
    ): array {
        $horasDecimais   = $this->tempoParaHorasDecimais($tempoImpressao);
        $custoFilamento  = $this->calcularCustoFilamento($pesoGramas, $precoMedioGrama);
        $custoEnergia    = $this->calcularCustoEnergia($horasDecimais, $custoEnergiaKwh);
        $custoDesgaste   = $this->calcularCustoDesgaste($horasDecimais, $custoDesgasteHora);
        $custoTotal      = $this->calcularCustoTotal($custoFilamento, $custoEnergia, $custoDesgaste);

        return [
            'custo_filamento' => $custoFilamento,
            'custo_energia'   => $custoEnergia,
            'custo_desgaste'  => $custoDesgaste,
            'custo_total'     => $custoTotal,
        ];
    }

    /**
     * @param  array<int, array{custo_filamento?: float, custo_energia?: float, custo_desgaste?: float}>  $custos
     * @return array{custo_filamento: float, custo_energia: float, custo_desgaste: float, custo_total: float}
     */
    public function somarCustos(array $custos): array
    {
        $custoFilamento = 0.0;
        $custoEnergia   = 0.0;
        $custoDesgaste  = 0.0;

        foreach ($custos as $custo) {
            $custoFilamento += (float) ($custo['custo_filamento'] ?? 0);
            $custoEnergia   += (float) ($custo['custo_energia'] ?? 0);
            $custoDesgaste  += (float) ($custo['custo_desgaste'] ?? 0);
        }

        return [
            'custo_filamento' => round($custoFilamento, 4),
            'custo_energia'   => round($custoEnergia, 4),
            'custo_desgaste'  => round($custoDesgaste, 4),
            'custo_total'     => $this->calcularCustoTotal($custoFilamento, $custoEnergia, $custoDesgaste),
        ];
    }

    /**
     * @return array{custo_filamento: float, custo_energia: float, custo_desgaste: float, custo_total: float}
     */
    public function formatarCustosResposta(array $custos): array
    {
        return [
            'custo_filamento' => round((float) ($custos['custo_filamento'] ?? 0), 4),
            'custo_energia'   => round((float) ($custos['custo_energia'] ?? 0), 4),
            'custo_desgaste'  => round((float) ($custos['custo_desgaste'] ?? 0), 4),
            'custo_total'     => round((float) ($custos['custo_total'] ?? 0), 4),
        ];
    }
}
