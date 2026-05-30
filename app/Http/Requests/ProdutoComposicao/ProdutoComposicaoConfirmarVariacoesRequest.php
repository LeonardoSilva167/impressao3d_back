<?php

namespace App\Http\Requests\ProdutoComposicao;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProdutoComposicaoConfirmarVariacoesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_composicao' => ['required', 'integer', Rule::exists('produto_composicoes', 'id')->whereNull('deleted_at')],
            'id_parte'      => ['nullable', 'integer', Rule::exists('projetos_impressao_partes', 'id')->whereNull('deleted_at')],
            'id_item_projeto' => ['nullable', 'integer', Rule::exists('projetos_impressao_parte_itens', 'id')->whereNull('deleted_at')],
        ];
    }

    public function messages(): array
    {
        return [
            'id_composicao.required' => 'A composição é obrigatória.',
            'id_composicao.exists'   => 'A composição informada não existe.',
            'id_parte.exists'        => 'A parte informada não existe.',
        ];
    }
}
