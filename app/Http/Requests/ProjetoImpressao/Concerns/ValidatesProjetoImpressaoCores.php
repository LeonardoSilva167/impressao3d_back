<?php

namespace App\Http\Requests\ProjetoImpressao\Concerns;

use Illuminate\Validation\Rule;

trait ValidatesProjetoImpressaoCores
{
    protected function projetoCoresRules(): array
    {
        return [
            'cores'                 => ['required', 'array', 'min:1'],
            'cores.*.id_cor'        => ['required', 'integer', Rule::exists('cores', 'id')->whereNull('deleted_at')],
            'cores.*.peso_gramas'   => ['required', 'numeric', 'gt:0'],
        ];
    }

    protected function projetoCoresMessages(): array
    {
        return [
            'cores.required'               => 'As cores do projeto são obrigatórias.',
            'cores.min'                    => 'O projeto deve conter ao menos uma cor.',
            'cores.*.id_cor.required'      => 'A cor é obrigatória.',
            'cores.*.id_cor.exists'        => 'A cor informada não existe.',
            'cores.*.peso_gramas.required' => 'O peso da cor é obrigatório.',
            'cores.*.peso_gramas.gt'       => 'O peso da cor deve ser maior que zero.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $tempoTotal = $this->input('tempo_total_horas');

            if ($tempoTotal !== null && preg_match('/^\d{2}:\d{2}$/', (string) $tempoTotal)) {
                [$horas, $minutos] = array_map('intval', explode(':', (string) $tempoTotal));

                if ($minutos > 59) {
                    $validator->errors()->add(
                        'tempo_total_horas',
                        'Os minutos do tempo total do projeto devem estar entre 00 e 59.'
                    );
                }

                if ($horas === 0 && $minutos === 0) {
                    $validator->errors()->add(
                        'tempo_total_horas',
                        'O tempo total do projeto deve ser maior que zero.'
                    );
                }
            }

            $cores = $this->input('cores', []);
            $ids   = array_column($cores, 'id_cor');

            if (count($ids) !== count(array_unique($ids))) {
                $validator->errors()->add('cores', 'Não é permitido repetir a mesma cor no projeto.');
            }

            $pesoTotal = $this->input('peso_total_gramas');

            if ($pesoTotal !== null && is_array($cores) && count($cores) > 0) {
                $soma = 0.0;

                foreach ($cores as $cor) {
                    $soma += (float) ($cor['peso_gramas'] ?? 0);
                }

                if (round($soma, 2) !== round((float) $pesoTotal, 2)) {
                    $validator->errors()->add(
                        'cores',
                        'A soma dos pesos das cores deve ser igual ao peso total do projeto.'
                    );
                }
            }
        });
    }
}
