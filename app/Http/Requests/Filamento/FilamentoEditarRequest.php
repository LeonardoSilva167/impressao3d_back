<?php

namespace App\Http\Requests\Filamento;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilamentoEditarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->replace($this->except(['codigo', 'resumo']));
    }

    public function rules(): array
    {
        return [
            'id'                => ['required', 'integer', Rule::exists('filamentos', 'id')->whereNull('deleted_at')],
            'id_tipo_material'  => ['required', 'integer', Rule::exists('tipos_materiais', 'id')->whereNull('deleted_at')],
            'id_cor'            => ['required', 'integer', Rule::exists('cores', 'id')->whereNull('deleted_at')],
            'id_linha_marca'    => ['required', 'integer', Rule::exists('linhas_marcas', 'id')->whereNull('deleted_at')],
            'id_marca'          => ['required', 'integer', Rule::exists('marcas', 'id')->whereNull('deleted_at')],
            'qtd'               => ['nullable', 'numeric', 'min:0'],
            'preco_medio_grama' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required'               => 'O identificador do filamento é obrigatório.',
            'id.exists'                 => 'Filamento não encontrado.',
            'id_tipo_material.required' => 'O tipo de material é obrigatório.',
            'id_tipo_material.exists'   => 'O tipo de material informado não existe.',
            'id_cor.required'           => 'A cor é obrigatória.',
            'id_cor.exists'             => 'A cor informada não existe.',
            'id_linha_marca.required'   => 'A linha de marca é obrigatória.',
            'id_linha_marca.exists'     => 'A linha de marca informada não existe.',
            'id_marca.required'         => 'A marca é obrigatória.',
            'id_marca.exists'           => 'A marca informada não existe.',
            'qtd.numeric'               => 'A quantidade deve ser um valor numérico.',
            'qtd.min'                   => 'A quantidade não pode ser negativa.',
            'preco_medio_grama.numeric' => 'O preço médio por grama deve ser um valor numérico.',
            'preco_medio_grama.min'     => 'O preço médio por grama não pode ser negativo.',
        ];
    }
}
