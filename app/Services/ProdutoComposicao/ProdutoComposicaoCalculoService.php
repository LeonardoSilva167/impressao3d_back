<?php

namespace App\Services\ProdutoComposicao;

use App\Services\Custo\CustoCalculoService;
use Exception;

class ProdutoComposicaoCalculoService
{
    private CustoCalculoService $_custoService;

    public function __construct()
    {
        $this->_custoService = new CustoCalculoService();
    }

    public function calcularCustoItem(float $pesoTotal, float $precoMedioGrama): float
    {
        return $this->_custoService->calcularCustoFilamento($pesoTotal, $precoMedioGrama);
    }

    public function calcularCustoTotal(array $custosItens): float
    {
        return round(array_sum($custosItens), 4);
    }

    public function tempoParaHorasDecimais(string $tempo): float
    {
        return $this->_custoService->tempoParaHorasDecimais($tempo);
    }

    public function tempoParaMinutos(string $tempo): int
    {
        if (!preg_match('/^\d{1,2}:\d{2}$/', $tempo)) {
            throw new Exception('Tempo de impressão inválido: ' . $tempo, 422);
        }

        [$horas, $minutos] = array_map('intval', explode(':', $tempo));

        if ($minutos > 59) {
            throw new Exception('Tempo de impressão inválido: ' . $tempo, 422);
        }

        return ($horas * 60) + $minutos;
    }

    public function minutosParaTempo(int $totalMinutos): string
    {
        if ($totalMinutos < 0) {
            throw new Exception('Tempo total de impressão inválido.', 422);
        }

        $horas   = intdiv($totalMinutos, 60);
        $minutos = $totalMinutos % 60;

        return sprintf('%02d:%02d', $horas, $minutos);
    }

    public function somarTempos(array $tempos): string
    {
        $totalMinutos = 0;

        foreach ($tempos as $tempo) {
            $totalMinutos += $this->tempoParaMinutos((string) $tempo);
        }

        return $this->minutosParaTempo($totalMinutos);
    }
}
