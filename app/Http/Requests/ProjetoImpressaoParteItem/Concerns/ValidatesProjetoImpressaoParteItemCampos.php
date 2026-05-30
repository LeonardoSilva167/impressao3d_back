<?php

namespace App\Http\Requests\ProjetoImpressaoParteItem\Concerns;

use App\Services\ProjetoImpressaoParteItem\ProjetoImpressaoParteItemConfig;
use App\Services\ProjetoImpressaoParteItem\ProjetoImpressaoParteItemTempoService;
use Illuminate\Validation\Rule;

trait ValidatesProjetoImpressaoParteItemCampos
{
    protected function itemCamposRules(): array
    {
        return [
            'id_projeto_impressao_parte' => ['required', 'integer', Rule::exists('projetos_impressao_partes', 'id')->whereNull('deleted_at')],
            'nome_item'                  => ['required', 'string', 'max:255'],
            'id_cor'                     => ['required', 'integer', Rule::exists('cores', 'id')->whereNull('deleted_at')],
            'altura_camada'              => ['required', 'numeric', 'min:0'],
            'temperatura_bico'           => ['required', 'integer', 'min:0'],
            'temperatura_mesa'           => ['required', 'integer', 'min:0'],
            'loops_parede'               => ['required', 'integer', 'min:1'],
            'tempo_impressao'            => ['required', 'string', 'regex:' . ProjetoImpressaoParteItemTempoService::FORMATO_REGEX],
            'peso_parte'                 => ['required', 'numeric', 'gt:0'],
            'peso_suporte'               => ['nullable', 'numeric', 'min:0'],
            'peso_corado'                => ['nullable', 'numeric', 'min:0'],
            'peso_torre'                 => ['nullable', 'numeric', 'min:0'],
            'usa_suporte'                => ['required', 'boolean'],
            'angulo_suporte'             => ['nullable', 'required_if:usa_suporte,true,1', 'numeric', 'min:0'],
            'tipo_suporte'               => ['nullable', 'required_if:usa_suporte,true,1', Rule::in(ProjetoImpressaoParteItemConfig::TIPOS_SUPORTE)],
            'distancia_z_inferior'       => ['nullable', 'numeric', 'min:0'],
            'quantidade_voltas_suporte'  => ['nullable', 'integer', 'min:1'],
            'usa_brim'                   => ['required', 'boolean'],
            'usa_engomagem'              => ['required', 'boolean'],
            'velocidade_engomagem'       => ['nullable', 'required_if:usa_engomagem,true,1', 'numeric', 'gt:0'],
            'fluxo_engomagem'            => ['nullable', 'required_if:usa_engomagem,true,1', 'numeric', 'gt:0'],
        ];
    }

    protected function itemCamposMessages(): array
    {
        return [
            'id_projeto_impressao_parte.required' => 'A parte do projeto é obrigatória.',
            'id_projeto_impressao_parte.exists'   => 'A parte do projeto informada não existe.',
            'nome_item.required'                  => 'O nome do item é obrigatório.',
            'id_cor.required'                     => 'A cor é obrigatória.',
            'id_cor.exists'                       => 'A cor informada não existe.',
            'altura_camada.required'              => 'A altura de camada é obrigatória.',
            'temperatura_bico.required'           => 'A temperatura do bico é obrigatória.',
            'temperatura_mesa.required'           => 'A temperatura da mesa é obrigatória.',
            'loops_parede.required'               => 'A quantidade de loops de parede é obrigatória.',
            'tempo_impressao.required'            => 'O tempo de impressão é obrigatório.',
            'tempo_impressao.regex'               => 'O tempo de impressão deve estar no formato H:mm ou HH:mm.',
            'peso_parte.required'                 => 'O peso da parte é obrigatório.',
            'peso_parte.gt'                       => 'O peso da parte deve ser maior que zero.',
            'usa_suporte.required'                => 'Informe se possui suporte.',
            'angulo_suporte.required_if'          => 'O ângulo de suporte é obrigatório quando possui suporte.',
            'tipo_suporte.required_if'              => 'O tipo de suporte é obrigatório quando possui suporte.',
            'distancia_z_inferior.numeric'          => 'A distância Z inferior deve ser um valor decimal.',
            'distancia_z_inferior.min'              => 'A distância Z inferior não pode ser negativa.',
            'quantidade_voltas_suporte.integer'     => 'A quantidade de voltas deve ser um número inteiro.',
            'quantidade_voltas_suporte.min'         => 'A quantidade de voltas deve ser no mínimo 1.',
            'usa_brim.required'                   => 'Informe se possui brim.',
            'usa_engomagem.required'              => 'Informe se possui engomagem.',
            'velocidade_engomagem.required_if'    => 'A velocidade é obrigatória quando possui engomagem.',
            'fluxo_engomagem.required_if'         => 'O fluxo é obrigatório quando possui engomagem.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $tempoImpressao = $this->input('tempo_impressao');

            if ($tempoImpressao !== null && preg_match(ProjetoImpressaoParteItemTempoService::FORMATO_REGEX, (string) $tempoImpressao)) {
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
