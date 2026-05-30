<?php

namespace App\Http\Requests\ModeloProduto;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModeloProdutoCadastrarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'descricao' => ['required', 'string', 'max:120'],
            'codigo'    => [
                'required',
                'string',
                'max:20',
                Rule::unique('modelos_produtos', 'codigo')->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'descricao.required' => 'A descrição é obrigatória.',
            'descricao.max'      => 'A descrição deve ter no máximo 120 caracteres.',
            'codigo.required'    => 'O código é obrigatório.',
            'codigo.max'         => 'O código deve ter no máximo 20 caracteres.',
            'codigo.unique'      => 'Já existe uma modelo de produto com este código.',
        ];
    }
}
