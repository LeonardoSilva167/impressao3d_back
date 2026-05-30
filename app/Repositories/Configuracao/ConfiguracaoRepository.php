<?php

namespace App\Repositories\Configuracao;

use App\Models\Configuracao;

class ConfiguracaoRepository
{
    public function consumirProximoCodigoBase(): string
    {
        $configuracao = Configuracao::whereNull('deleted_at')
            ->lockForUpdate()
            ->first();

        if (!$configuracao) {
            throw new \RuntimeException('Configuração do sistema não encontrada.', 500);
        }

        $codigoAtual = (int) $configuracao->proximo_codigo_base;

        $configuracao->update([
            'proximo_codigo_base' => $codigoAtual + 1,
        ]);

        return (string) $codigoAtual;
    }

    public function getProximoCodigoBase(): int
    {
        $valor = Configuracao::whereNull('deleted_at')->value('proximo_codigo_base');

        return (int) ($valor ?? 1000);
    }

    /**
     * @return array{custo_energia_kwh: float, custo_desgaste_hora: float}
     */
    public function getCustosConfig(): array
    {
        $configuracao = Configuracao::whereNull('deleted_at')->first();

        if (!$configuracao) {
            throw new \RuntimeException('Configuração do sistema não encontrada.', 500);
        }

        return [
            'custo_energia_kwh'   => round((float) $configuracao->custo_energia_kwh, 4),
            'custo_desgaste_hora' => round((float) $configuracao->custo_desgaste_hora, 4),
        ];
    }
}
