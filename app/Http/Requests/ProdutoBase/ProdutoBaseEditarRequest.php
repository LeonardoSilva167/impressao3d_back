<?php

namespace App\Http\Requests\ProdutoBase;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProdutoBaseEditarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->replace($this->except(['sku_base', 'codigo_base']));
    }

    public function rules(): array
    {
        return [
            'id'                => ['required', 'integer', Rule::exists('produtos_base', 'id')->whereNull('deleted_at')],
            'descricao_produto' => ['required', 'string', 'max:120'],
            'id_categoria'      => ['required', 'integer', Rule::exists('categorias_produtos', 'id')->whereNull('deleted_at')],
            'id_modelo'         => ['required', 'integer', Rule::exists('modelos_produtos', 'id')->whereNull('deleted_at')],
            'id_linha'          => ['nullable', 'integer', Rule::exists('linhas_produtos', 'id')->whereNull('deleted_at')],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required'                => 'O identificador do produto é obrigatório.',
            'id.exists'                  => 'Produto base não encontrado.',
            'descricao_produto.required' => 'A descrição do produto é obrigatória.',
            'descricao_produto.max'      => 'A descrição do produto deve ter no máximo 120 caracteres.',
            'id_categoria.required'      => 'A categoria é obrigatória.',
            'id_categoria.exists'        => 'A categoria informada não existe.',
            'id_modelo.required'         => 'O modelo é obrigatório.',
            'id_modelo.exists'           => 'O modelo informado não existe.',
            'id_linha.exists'            => 'A linha informada não existe.',
        ];
    }
}
