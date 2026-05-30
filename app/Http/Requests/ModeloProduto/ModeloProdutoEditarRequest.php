<?php

namespace App\Http\Requests\ModeloProduto;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModeloProdutoEditarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id'        => ['required', 'integer', Rule::exists('modelos_produtos', 'id')->whereNull('deleted_at')],
            'descricao' => ['required', 'string', 'max:120'],
            'codigo'    => [
                'required',
                'string',
                'max:20',
                Rule::unique('modelos_produtos', 'codigo')
                    ->ignore($this->input('id'))
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required'        => 'O identificador é obrigatório.',
            'id.exists'          => 'Modelo de produto não encontrada.',
            'descricao.required' => 'A descrição é obrigatória.',
            'descricao.max'      => 'A descrição deve ter no máximo 120 caracteres.',
            'codigo.required'    => 'O código é obrigatório.',
            'codigo.max'         => 'O código deve ter no máximo 20 caracteres.',
            'codigo.unique'      => 'Já existe uma modelo de produto com este código.',
        ];
    }
}
