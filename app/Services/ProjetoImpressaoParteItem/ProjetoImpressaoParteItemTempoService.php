<?php

namespace App\Services\ProjetoImpressaoParteItem;

use Exception;

class ProjetoImpressaoParteItemTempoService
{
    public const FORMATO_REGEX = '/^\d{1,2}:\d{2}$/';

    public function validarFormato(string $tempo): void
    {
        if (!preg_match(self::FORMATO_REGEX, $tempo)) {
            throw new Exception('O tempo de impressão deve estar no formato H:mm ou HH:mm.', 422);
        }

        [$horas, $minutos] = $this->parseTempo($tempo);

        if ($minutos > 59) {
            throw new Exception('Os minutos do tempo de impressão devem estar entre 00 e 59.', 422);
        }

        if ($horas === 0 && $minutos === 0) {
            throw new Exception('O tempo de impressão deve ser maior que 00:00.', 422);
        }
    }

    public function normalizar(string $tempo): ?string
    {
        if (!preg_match(self::FORMATO_REGEX, $tempo)) {
            return null;
        }

        [$horas, $minutos] = $this->parseTempo($tempo);

        return sprintf('%02d:%02d', $horas, $minutos);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function parseTempo(string $tempo): array
    {
        return array_map('intval', explode(':', $tempo));
    }
}
