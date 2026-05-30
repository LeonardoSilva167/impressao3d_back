<?php

namespace App\Http\Requests\ProdutoComposicao;

use App\Models\ProdutoVariacao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProdutoComposicaoSalvarCoresParteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (!$this->filled('id_composicao') && $this->filled('id_composicao_produto')) {
            $this->merge(['id_composicao' => $this->input('id_composicao_produto')]);
        }
    }

    public function rules(): array
    {
        return [
            'id_composicao'                       => ['required', 'integer', Rule::exists('produto_composicoes', 'id')->whereNull('deleted_at')],
            'id_parte'                            => ['required', 'integer', Rule::exists('projetos_impressao_partes', 'id')->whereNull('deleted_at')],
            'itens'                               => ['required', 'array', 'min:1'],
            'itens.*.id_item_projeto'             => ['required', 'integer', Rule::exists('projetos_impressao_parte_itens', 'id')->whereNull('deleted_at')],
            'itens.*.cores_primarias'             => ['nullable', 'array'],
            'itens.*.cores_primarias.*'           => ['integer', Rule::exists('cores', 'id')->whereNull('deleted_at')],
            'itens.*.cores_secundarias'           => ['nullable', 'array'],
            'itens.*.cores_secundarias.*'         => ['integer', Rule::exists('cores', 'id')->whereNull('deleted_at')],
            'itens.*.cores_terciarias'            => ['nullable', 'array'],
            'itens.*.cores_terciarias.*'          => ['integer', Rule::exists('cores', 'id')->whereNull('deleted_at')],
            'itens.*.cores'                       => ['nullable', 'array'],
            'itens.*.cores.*'                     => ['integer', Rule::exists('cores', 'id')->whereNull('deleted_at')],
        ];
    }

    public function messages(): array
    {
        return [
            'id_composicao.required'           => 'A composição é obrigatória.',
            'id_composicao.exists'             => 'A composição informada não existe.',
            'id_parte.required'                => 'A parte é obrigatória.',
            'id_parte.exists'                  => 'A parte informada não existe.',
            'itens.required'                   => 'Os itens da parte são obrigatórios.',
            'itens.min'                        => 'Informe ao menos um item.',
            'itens.*.id_item_projeto.required' => 'O item do projeto é obrigatório.',
            'itens.*.id_item_projeto.exists'   => 'O item do projeto informado não existe.',
        ];
    }
}
