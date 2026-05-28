<?php

namespace App\Http\Requests\ProjetoImpressao\Concerns;

use App\Services\ProjetoImpressao\ProjetoImpressaoTempoService;

trait PreparesProjetoImpressaoPayload
{
    protected function prepareProjetoImpressaoPayload(): void
    {
        $tempoService = new ProjetoImpressaoTempoService();

        $tempoRaw = $this->input('tempo_total_horas', $this->input('tempo_total_projeto'));
        $pesoRaw  = $this->input('peso_total_gramas', $this->input('peso_total_projeto'));

        $tempoConvertido = $tempoRaw;
        try {
            $tempoConvertido = $tempoService->converterParaHorasMinutos($tempoRaw);
        } catch (\Exception) {
            // Mantém valor original para a validação retornar erro amigável.
        }

        $pesoConvertido = $pesoRaw;
        try {
            $pesoConvertido = $tempoService->normalizarValorNumerico($pesoRaw, 'O peso total do projeto');
        } catch (\Exception) {
            // Mantém valor original para a validação retornar erro amigável.
        }

        $coresNormalizadas = array_map(function ($cor) use ($tempoService) {
            $item = is_array($cor) ? $cor : (array) $cor;

            $pesoCor = $item['peso_gramas'] ?? null;

            try {
                $pesoCor = $tempoService->normalizarValorNumerico($pesoCor, 'O peso da cor');
            } catch (\Exception) {
                // Mantém valor original para a validação retornar erro amigável.
            }

            return [
                'id_cor'      => $item['id_cor'] ?? null,
                'peso_gramas' => $pesoCor,
            ];
        }, $this->input('cores', []));

        $this->merge([
            'bico_padrao'       => $this->input('bico_padrao') ?? '0.4',
            'tempo_total_horas' => $tempoConvertido,
            'peso_total_gramas' => $pesoConvertido,
            'cores'             => $coresNormalizadas,
        ]);
    }

    protected function projetoCamposRules(): array
    {
        return [
            'tempo_total_horas' => ['required', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'peso_total_gramas' => ['required', 'numeric', 'gt:0'],
        ];
    }

    protected function projetoCamposMessages(): array
    {
        return [
            'tempo_total_horas.required' => 'O tempo total do projeto é obrigatório.',
            'tempo_total_horas.regex'    => 'O tempo total do projeto deve estar no formato HH:mm.',
            'peso_total_gramas.required' => 'O peso total do projeto é obrigatório.',
            'peso_total_gramas.gt'       => 'O peso total do projeto deve ser maior que zero.',
        ];
    }
}
