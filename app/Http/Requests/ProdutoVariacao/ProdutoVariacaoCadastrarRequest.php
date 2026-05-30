<?php

namespace App\Http\Requests\ProdutoVariacao;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProdutoVariacaoCadastrarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->replace($this->except(['sku']));
    }

    public function rules(): array
    {
        return [
            'id_produto_base'    => ['required', 'integer', Rule::exists('produtos_base', 'id')->whereNull('deleted_at')],
            'cores_primarias'    => ['required', 'array', 'min:1'],
            'cores_primarias.*'  => ['integer', Rule::exists('cores', 'id')->whereNull('deleted_at')],
            'cores_secundarias'  => ['nullable', 'array'],
            'cores_secundarias.*' => ['integer', Rule::exists('cores', 'id')->whereNull('deleted_at')],
            'cores_terciarias'   => ['nullable', 'array'],
            'cores_terciarias.*' => ['integer', Rule::exists('cores', 'id')->whereNull('deleted_at')],
        ];
    }

    public function messages(): array
    {
        return [
            'id_produto_base.required'   => 'O produto base é obrigatório.',
            'id_produto_base.exists'     => 'O produto base informado não existe.',
            'cores_primarias.required'   => 'Informe ao menos uma cor primária.',
            'cores_primarias.min'        => 'Informe ao menos uma cor primária.',
            'cores_primarias.*.exists'   => 'Uma das cores primárias informadas não existe.',
            'cores_secundarias.*.exists' => 'Uma das cores secundárias informadas não existe.',
            'cores_terciarias.*.exists'  => 'Uma das cores terciárias informadas não existe.',
        ];
    }
}
