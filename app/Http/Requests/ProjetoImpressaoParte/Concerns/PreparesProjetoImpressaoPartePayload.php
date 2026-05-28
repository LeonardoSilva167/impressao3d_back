<?php

namespace App\Http\Requests\ProjetoImpressaoParte\Concerns;

use App\Services\ProjetoImpressaoParte\ProjetoImpressaoParteCalculoService;
use App\Services\ProjetoImpressaoParte\ProjetoImpressaoParteConfig;

trait PreparesProjetoImpressaoPartePayload
{
    protected function prepareProjetoImpressaoPartePayload(): void
    {
        $calculoService = new ProjetoImpressaoParteCalculoService();

        $pesoParte = $this->input('peso_parte');
        try {
            $pesoParte = $calculoService->normalizarPeso($pesoParte, 'O peso da parte', true);
        } catch (\Exception) {
            // Mantém valor original para a validação retornar erro amigável.
        }

        $pesoSuporte = $this->normalizarPesoOpcional($calculoService, $this->input('peso_suporte'), 'O peso de suporte');
        $pesoCorado  = $this->normalizarPesoOpcional($calculoService, $this->input('peso_corado'), 'O peso corado');
        $pesoTorre   = $this->normalizarPesoOpcional($calculoService, $this->input('peso_torre'), 'O peso da torre');

        $this->merge([
            'altura_camada'    => $this->input('altura_camada') ?? 0.20,
            'loops_parede'     => $this->input('loops_parede') ?? 2,
            'temperatura_bico' => $this->input('temperatura_bico') ?? ProjetoImpressaoParteConfig::TEMPERATURA_BICO_PADRAO,
            'temperatura_mesa' => $this->input('temperatura_mesa') ?? ProjetoImpressaoParteConfig::TEMPERATURA_MESA_PADRAO,
            'peso_parte'       => $pesoParte,
            'peso_suporte'     => $pesoSuporte,
            'peso_corado'      => $pesoCorado,
            'peso_torre'       => $pesoTorre,
        ]);
    }

    private function normalizarPesoOpcional(
        ProjetoImpressaoParteCalculoService $calculoService,
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
