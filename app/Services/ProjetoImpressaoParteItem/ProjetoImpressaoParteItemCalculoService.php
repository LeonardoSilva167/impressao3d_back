<?php

namespace App\Services\ProjetoImpressaoParteItem;

use Exception;

class ProjetoImpressaoParteItemCalculoService
{
    public function normalizarPeso(mixed $valor, string $campo, bool $obrigatorio = false): float
    {
        if ($valor === null || $valor === '') {
            if ($obrigatorio) {
                throw new Exception("{$campo} é obrigatório.", 422);
            }

            return 0.0;
        }

        if (!is_numeric($valor)) {
            throw new Exception("{$campo} inválido.", 422);
        }

        $peso = round((float) $valor, 2);

        if ($obrigatorio && $peso <= 0) {
            throw new Exception("{$campo} deve ser maior que zero.", 422);
        }

        if ($peso < 0) {
            throw new Exception("{$campo} não pode ser negativo.", 422);
        }

        return $peso;
    }

    public function calcularPesoTotal(
        float $pesoParte,
        float $pesoSuporte = 0,
        float $pesoCorado = 0,
        float $pesoTorre = 0
    ): float {
        return round($pesoParte + $pesoSuporte + $pesoCorado + $pesoTorre, 2);
    }

    public function appendCamposVirtuais(array|object $record): array
    {
        $data = is_array($record) ? $record : (array) $record;

        $data['peso_total'] = $this->calcularPesoTotal(
            (float) ($data['peso_parte'] ?? 0),
            (float) ($data['peso_suporte'] ?? 0),
            (float) ($data['peso_corado'] ?? 0),
            (float) ($data['peso_torre'] ?? 0),
        );

        return $data;
    }
}
