<?php

namespace App\Http\Requests\ProjetoImpressaoParte\Concerns;

use App\Services\ProjetoImpressaoParte\ProjetoImpressaoParteConfig;
use Illuminate\Validation\Rule;

trait ValidatesProjetoImpressaoParteCampos
{
    protected function parteCamposRules(): array
    {
        return [
            'id_projeto_impressao'      => ['required', 'integer', Rule::exists('projetos_impressao', 'id')->whereNull('deleted_at')],
            'nome_parte'                => ['required', 'string', 'max:255'],
            'altura_camada'             => ['required', 'numeric', 'min:0'],
            'temperatura_bico'          => ['required', 'integer', 'min:0'],
            'temperatura_mesa'          => ['required', 'integer', 'min:0'],
            'tempo_impressao'           => ['required', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'peso_parte'                => ['required', 'numeric', 'gt:0'],
            'peso_suporte'              => ['nullable', 'numeric', 'min:0'],
            'peso_corado'               => ['nullable', 'numeric', 'min:0'],
            'peso_torre'                => ['nullable', 'numeric', 'min:0'],
            'usa_suporte'               => ['required', 'boolean'],
            'angulo_suporte'            => ['nullable', 'required_if:usa_suporte,true,1', 'numeric', 'min:0'],
            'tipo_suporte'              => ['nullable', 'required_if:usa_suporte,true,1', Rule::in(ProjetoImpressaoParteConfig::TIPOS_SUPORTE)],
            'distancia_z_inferior'      => ['nullable', 'required_if:usa_suporte,true,1', 'numeric', 'min:0'],
            'quantidade_voltas_suporte' => ['nullable', 'required_if:usa_suporte,true,1', 'integer', 'min:1'],
            'usa_brim'                  => ['required', 'boolean'],
            'usa_engomagem'             => ['required', 'boolean'],
            'velocidade_engomagem'      => ['nullable', 'required_if:usa_engomagem,true,1', 'numeric', 'gt:0'],
            'fluxo_engomagem'           => ['nullable', 'required_if:usa_engomagem,true,1', 'numeric', 'gt:0'],
            'loops_parede'              => ['required', 'integer', 'min:1'],
        ];
    }

    protected function parteCamposMessages(): array
    {
        return [
            'id_projeto_impressao.required'      => 'O projeto de impressão é obrigatório.',
            'id_projeto_impressao.exists'        => 'O projeto de impressão informado não existe.',
            'nome_parte.required'                => 'O nome da parte é obrigatório.',
            'altura_camada.required'             => 'A altura de camada é obrigatória.',
            'temperatura_bico.required'          => 'A temperatura do bico é obrigatória.',
            'temperatura_mesa.required'          => 'A temperatura da mesa é obrigatória.',
            'tempo_impressao.required'           => 'O tempo de impressão é obrigatório.',
            'tempo_impressao.regex'              => 'O tempo de impressão deve estar no formato HH:mm.',
            'peso_parte.required'                => 'O peso da parte é obrigatório.',
            'peso_parte.gt'                      => 'O peso da parte deve ser maior que zero.',
            'peso_suporte.min'                   => 'O peso de suporte não pode ser negativo.',
            'peso_corado.min'                    => 'O peso corado não pode ser negativo.',
            'peso_torre.min'                     => 'O peso da torre não pode ser negativo.',
            'usa_suporte.required'               => 'Informe se usa suporte.',
            'angulo_suporte.required_if'         => 'O ângulo de suporte é obrigatório quando usa suporte.',
            'tipo_suporte.required_if'           => 'O tipo de suporte é obrigatório quando usa suporte.',
            'distancia_z_inferior.required_if'   => 'A distância Z inferior é obrigatória quando usa suporte.',
            'quantidade_voltas_suporte.required_if'=> 'A quantidade de voltas de suporte é obrigatória quando usa suporte.',
            'usa_brim.required'                  => 'Informe se usa brim.',
            'usa_engomagem.required'             => 'Informe se usa engomagem.',
            'velocidade_engomagem.required_if'   => 'A velocidade de engomagem é obrigatória quando usa engomagem.',
            'fluxo_engomagem.required_if'        => 'O fluxo de engomagem é obrigatório quando usa engomagem.',
            'loops_parede.required'              => 'A quantidade de loops de parede é obrigatória.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $tempoImpressao = $this->input('tempo_impressao');

            if ($tempoImpressao !== null && preg_match('/^\d{2}:\d{2}$/', (string) $tempoImpressao)) {
                [$horas, $minutos] = array_map('intval', explode(':', (string) $tempoImpressao));

                if ($minutos > 59) {
                    $validator->errors()->add(
                        'tempo_impressao',
                        'Os minutos do tempo de impressão devem estar entre 00 e 59.'
                    );
                }

                if ($horas === 0 && $minutos === 0) {
                    $validator->errors()->add(
                        'tempo_impressao',
                        'O tempo de impressão deve ser maior que 00:00.'
                    );
                }
            }
        });
    }
}
