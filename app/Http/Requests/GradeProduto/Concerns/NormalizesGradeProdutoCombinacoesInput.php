<?php

namespace App\Http\Requests\GradeProduto\Concerns;

trait NormalizesGradeProdutoCombinacoesInput
{
    protected function prepareForValidation(): void
    {
        if (!$this->has('combinacoes') || !is_array($this->input('combinacoes'))) {
            return;
        }

        $combinacoes = array_map(function ($combinacao) {
            $combinacao = (array) $combinacao;

            if (!isset($combinacao['partes']) || !is_array($combinacao['partes'])) {
                return $combinacao;
            }

            $combinacao['partes'] = array_map(function ($parte) {
                $parte = (array) $parte;

                if (!isset($parte['id_parte_projeto']) && isset($parte['id_parte'])) {
                    $parte['id_parte_projeto'] = $parte['id_parte'];
                }

                return $parte;
            }, $combinacao['partes']);

            return $combinacao;
        }, $this->input('combinacoes'));

        $this->merge(['combinacoes' => $combinacoes]);
    }
}
