<?php

namespace App\Http\Requests\ProdutoComposicao;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProdutoComposicaoCadastrarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->sharedRules();
    }

    public function messages(): array
    {
        return $this->sharedMessages();
    }

    protected function sharedRules(): array
    {
        return [
            'id_produto'                          => ['required', 'integer', Rule::exists('produtos_base', 'id')->whereNull('deleted_at')],
            'id_projeto_impressao'                => ['required', 'integer', Rule::exists('projetos_impressao', 'id')->whereNull('deleted_at')],
            'variacoes'                           => ['required', 'array', 'min:1'],
            'variacoes.*.id_produto_variacao'     => ['required', 'integer', Rule::exists('produto_variacoes', 'id')->whereNull('deleted_at')],
            'variacoes.*.itens'                   => ['required', 'array', 'min:1'],
            'variacoes.*.itens.*.id_item_projeto' => ['required', 'integer', Rule::exists('projetos_impressao_parte_itens', 'id')->whereNull('deleted_at')],
            'variacoes.*.itens.*.id_filamento'    => ['required', 'integer', Rule::exists('filamentos', 'id')->whereNull('deleted_at')],
            'variacoes.*.itens.*.peso_total'      => ['required', 'numeric', 'gt:0'],
            'variacoes.*.itens.*.tempo_impressao' => ['required', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'variacoes.*.itens.*.preco_medio_grama' => ['nullable', 'numeric', 'gte:0'],
        ];
    }

    protected function sharedMessages(): array
    {
        return [
            'id_produto.required'                          => 'O produto base é obrigatório.',
            'id_produto.exists'                              => 'O produto base informado não existe.',
            'id_projeto_impressao.required'                => 'O projeto de impressão é obrigatório.',
            'id_projeto_impressao.exists'                    => 'O projeto de impressão informado não existe.',
            'variacoes.required'                             => 'As variações são obrigatórias.',
            'variacoes.min'                                  => 'Informe ao menos uma variação.',
            'variacoes.*.id_produto_variacao.required'       => 'A variação do produto é obrigatória.',
            'variacoes.*.id_produto_variacao.exists'         => 'A variação informada não existe.',
            'variacoes.*.itens.required'                     => 'Os itens da variação são obrigatórios.',
            'variacoes.*.itens.min'                          => 'Informe ao menos um item por variação.',
            'variacoes.*.itens.*.id_item_projeto.required'   => 'O item do projeto é obrigatório.',
            'variacoes.*.itens.*.id_item_projeto.exists'     => 'O item do projeto informado não existe.',
            'variacoes.*.itens.*.id_filamento.required'      => 'O filamento é obrigatório.',
            'variacoes.*.itens.*.id_filamento.exists'        => 'O filamento informado não existe.',
            'variacoes.*.itens.*.peso_total.required'        => 'O peso total é obrigatório.',
            'variacoes.*.itens.*.peso_total.gt'              => 'O peso total deve ser maior que zero.',
            'variacoes.*.itens.*.tempo_impressao.required'   => 'O tempo de impressão é obrigatório.',
            'variacoes.*.itens.*.tempo_impressao.regex'      => 'O tempo de impressão deve estar no formato HH:mm.',
            'variacoes.*.itens.*.preco_medio_grama.numeric'  => 'O preço médio por grama deve ser numérico.',
            'variacoes.*.itens.*.preco_medio_grama.gte'      => 'O preço médio por grama não pode ser negativo.',
        ];
    }
}
