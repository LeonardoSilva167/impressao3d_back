<?php

namespace App\Services\ProjetoImpressao;

use Exception;

class ProjetoImpressaoTempoService
{
    public function normalizarValorNumerico(mixed $valor, string $campo = 'Valor'): float
    {
        if ($valor === null || $valor === '') {
            throw new Exception("{$campo} é obrigatório.", 422);
        }

        if (!is_numeric($valor)) {
            throw new Exception("{$campo} inválido.", 422);
        }

        return round((float) $valor, 2);
    }

    public function converterParaHorasMinutos(mixed $tempo): string
    {
        if ($tempo === null || $tempo === '') {
            throw new Exception('O tempo total do projeto é obrigatório.', 422);
        }

        $tempo = is_string($tempo) ? trim($tempo) : $tempo;

        if (is_string($tempo) && preg_match('/^\d{2}:\d{2}$/', $tempo)) {
            $this->validarFormatoHorasMinutos($tempo);

            return $tempo;
        }

        $horasDecimais = $this->extrairHorasDecimais($tempo);
        $horas         = (int) floor($horasDecimais);
        $minutos       = (int) round(($horasDecimais - $horas) * 60);

        if ($minutos >= 60) {
            $horas  += intdiv($minutos, 60);
            $minutos = $minutos % 60;
        }

        if ($horas === 0 && $minutos === 0) {
            throw new Exception('O tempo total do projeto deve ser maior que zero.', 422);
        }

        return sprintf('%02d:%02d', $horas, $minutos);
    }

    public function validarFormatoHorasMinutos(string $tempo): void
    {
        if (!preg_match('/^\d{2}:\d{2}$/', $tempo)) {
            throw new Exception('O tempo total do projeto deve estar no formato HH:mm.', 422);
        }

        [$horas, $minutos] = array_map('intval', explode(':', $tempo));

        if ($minutos > 59) {
            throw new Exception('Os minutos do tempo total do projeto devem estar entre 00 e 59.', 422);
        }

        if ($horas === 0 && $minutos === 0) {
            throw new Exception('O tempo total do projeto deve ser maior que zero.', 422);
        }
    }

    private function extrairHorasDecimais(mixed $tempo): float
    {
        if (is_numeric($tempo)) {
            return (float) $tempo;
        }

        $tempo = trim((string) $tempo);

        if (preg_match('/^(\d+(?:\.\d+)?)\s*h$/i', $tempo, $matches)) {
            return (float) $matches[1];
        }

        if (is_numeric($tempo)) {
            return (float) $tempo;
        }

        throw new Exception(
            'O tempo total deve estar no formato decimal, HH:mm ou no padrão MakerWorld (ex: 3.5, 3.5h, 5h).',
            422
        );
    }
}
