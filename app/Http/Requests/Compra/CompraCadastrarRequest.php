<?php

namespace App\Http\Requests\Compra;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompraCadastrarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'valor_frete'    => $this->input('valor_frete') ?? 0,
            'valor_desconto' => $this->input('valor_desconto') ?? 0,
            'valor_taxa'     => $this->input('valor_taxa') ?? 0,
            'valor_imposto'  => $this->input('valor_imposto') ?? 0,
        ]);
    }

    public function rules(): array
    {
        return [
            'id_plataforma_compra' => ['required', 'integer', Rule::exists('plataforma_compras', 'id')->whereNull('deleted_at')],
            'data_compra'          => ['required', 'date'],
            'numero_pedido'        => ['nullable', 'string', 'max:100'],
            'valor_frete'          => ['required', 'numeric', 'min:0'],
            'valor_desconto'       => ['required', 'numeric', 'min:0'],
            'valor_taxa'           => ['required', 'numeric', 'min:0'],
            'valor_imposto'        => ['required', 'numeric', 'min:0'],
            'valor_total'          => ['required', 'numeric', 'min:0'],
            'observacao'           => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_plataforma_compra.required' => 'A plataforma de compra é obrigatória.',
            'id_plataforma_compra.exists'   => 'A plataforma de compra informada não existe.',
            'data_compra.required'          => 'A data da compra é obrigatória.',
            'data_compra.date'              => 'A data da compra deve ser uma data válida.',
            'valor_frete.required'          => 'O valor do frete é obrigatório.',
            'valor_desconto.required'       => 'O valor do desconto é obrigatório.',
            'valor_taxa.required'           => 'O valor da taxa é obrigatório.',
            'valor_imposto.required'        => 'O valor do imposto é obrigatório.',
            'valor_total.required'          => 'O valor total é obrigatório.',
        ];
    }
}
