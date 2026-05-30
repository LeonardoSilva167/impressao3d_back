<?php

namespace App\Http\Requests\ProdutoComposicao;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProdutoComposicaoEditarRequest extends ProdutoComposicaoCadastrarRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if (!$this->filled('id_produto') && $this->filled('id')) {
            $idProduto = DB::table('produto_composicoes')
                ->where('id', $this->input('id'))
                ->whereNull('deleted_at')
                ->value('id_produto');

            if ($idProduto !== null) {
                $this->merge([
                    'id_produto' => $idProduto,
                ]);
            }
        }
    }

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
