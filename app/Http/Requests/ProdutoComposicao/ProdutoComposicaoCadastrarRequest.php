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

    protected function prepareForValidation(): void
    {
        if (!$this->filled('id_produto') && $this->filled('id_produto_base')) {
            $this->merge([
                'id_produto' => $this->input('id_produto_base'),
            ]);
        }
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
            'id_produto'           => ['required', 'integer', Rule::exists('produtos_base', 'id')->whereNull('deleted_at')],
            'id_projeto_impressao' => ['required', 'integer', Rule::exists('projetos_impressao', 'id')->whereNull('deleted_at')],
        ];
    }

    protected function sharedMessages(): array
    {
        return [
            'id_produto.required'           => 'O produto base é obrigatório.',
            'id_produto.exists'             => 'O produto base informado não existe.',
            'id_projeto_impressao.required' => 'O projeto de impressão é obrigatório.',
            'id_projeto_impressao.exists'   => 'O projeto de impressão informado não existe.',
        ];
    }
}
