<?php

namespace App\Http\Requests\ProdutoComposicao;

use Illuminate\Validation\Rule;

class ProdutoComposicaoEditarRequest extends ProdutoComposicaoCadastrarRequest
{
    public function rules(): array
    {
        return array_merge([
            'id' => ['required', 'integer', Rule::exists('produto_composicoes', 'id')->whereNull('deleted_at')],
        ], $this->sharedRules());
    }

    public function messages(): array
    {
        return array_merge([
            'id.required' => 'O identificador da composição é obrigatório.',
            'id.exists'   => 'A composição informada não existe.',
        ], $this->sharedMessages());
    }
}
