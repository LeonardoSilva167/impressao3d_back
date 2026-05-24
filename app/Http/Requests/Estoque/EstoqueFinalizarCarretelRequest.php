<?php

namespace App\Http\Requests\Estoque;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EstoqueFinalizarCarretelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_filamento' => ['required', 'integer', Rule::exists('filamentos', 'id')->whereNull('deleted_at')],
            'observacao'   => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_filamento.required' => 'O filamento é obrigatório.',
            'id_filamento.exists'   => 'O filamento informado não existe.',
        ];
    }
}
