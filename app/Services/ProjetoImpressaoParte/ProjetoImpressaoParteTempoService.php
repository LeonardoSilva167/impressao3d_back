<?php

namespace App\Services\ProjetoImpressaoParte;

use Exception;

class ProjetoImpressaoParteTempoService
{
    public function validarFormato(string $tempo): void
    {
        if (!preg_match('/^\d{2}:\d{2}$/', $tempo)) {
            throw new Exception('O tempo de impressão deve estar no formato HH:mm.', 422);
        }

        [$horas, $minutos] = array_map('intval', explode(':', $tempo));

        if ($minutos > 59) {
            throw new Exception('Os minutos do tempo de impressão devem estar entre 00 e 59.', 422);
        }

        if ($horas === 0 && $minutos === 0) {
            throw new Exception('O tempo de impressão deve ser maior que 00:00.', 422);
        }
    }
}
