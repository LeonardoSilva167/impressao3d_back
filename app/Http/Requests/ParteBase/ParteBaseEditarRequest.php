<?php

namespace App\Http\Requests\ParteBase;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ParteBaseEditarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id'        => ['required', 'integer', Rule::exists('partes_base', 'id')->whereNull('deleted_at')],
            'descricao' => ['required', 'string', 'max:120'],
            'codigo'    => [
                'required',
                'string',
                'max:20',
                Rule::unique('partes_base', 'codigo')
                    ->ignore($this->input('id'))
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required'        => 'O identificador é obrigatório.',
            'id.exists'          => 'Parte base não encontrada.',
            'descricao.required' => 'A descrição é obrigatória.',
            'descricao.max'      => 'A descrição deve ter no máximo 120 caracteres.',
            'codigo.required'    => 'O código é obrigatório.',
            'codigo.max'         => 'O código deve ter no máximo 20 caracteres.',
            'codigo.unique'      => 'Já existe uma parte base com este código.',
        ];
    }
}
