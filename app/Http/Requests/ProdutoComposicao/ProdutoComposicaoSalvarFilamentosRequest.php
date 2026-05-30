<?php

namespace App\Http\Requests\ProdutoComposicao;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProdutoComposicaoSalvarFilamentosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_composicao'                          => ['required', 'integer', Rule::exists('produto_composicoes', 'id')->whereNull('deleted_at')],
            'id_parte'                               => ['nullable', 'integer', Rule::exists('projetos_impressao_partes', 'id')->whereNull('deleted_at')],
            'filamentos'                             => ['required', 'array', 'min:1'],
            'filamentos.*.id_variacao'               => ['required', 'integer', Rule::exists('produto_variacoes', 'id')->whereNull('deleted_at')],
            'filamentos.*.id_filamento'              => ['required', 'integer', Rule::exists('filamentos', 'id')->whereNull('deleted_at')],
            'filamentos.*.peso_item'                 => ['required', 'numeric', 'gt:0'],
            'filamentos.*.preco_medio_grama'         => ['nullable', 'numeric', 'gte:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_composicao.required'                     => 'A composição é obrigatória.',
            'id_composicao.exists'                       => 'A composição informada não existe.',
            'filamentos.required'                        => 'Os filamentos são obrigatórios.',
            'filamentos.min'                             => 'Informe ao menos um filamento.',
            'filamentos.*.id_variacao.required'          => 'A variação é obrigatória.',
            'filamentos.*.id_variacao.exists'            => 'A variação informada não existe.',
            'filamentos.*.id_filamento.required'         => 'O filamento é obrigatório.',
            'filamentos.*.id_filamento.exists'           => 'O filamento informado não existe.',
            'filamentos.*.peso_item.required'            => 'O peso do item é obrigatório.',
            'filamentos.*.peso_item.gt'                  => 'O peso do item deve ser maior que zero.',
            'filamentos.*.preco_medio_grama.numeric'     => 'O preço médio por grama deve ser numérico.',
            'filamentos.*.preco_medio_grama.gte'         => 'O preço médio por grama não pode ser negativo.',
        ];
    }
}
