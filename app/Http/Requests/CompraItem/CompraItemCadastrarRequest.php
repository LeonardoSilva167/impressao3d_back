<?php

namespace App\Http\Requests\CompraItem;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompraItemCadastrarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_compra'             => ['required', 'integer', Rule::exists('compras', 'id')->whereNull('deleted_at')],
            'id_item'               => ['required', 'integer', Rule::exists('itens', 'id')->whereNull('deleted_at')],
            'qtd_compra'            => ['required', 'numeric', 'gt:0'],
            'qtd_interna'           => ['nullable', 'numeric', 'gt:0'],
            'gramatura_filamento'   => ['nullable', 'integer', Rule::in([500, 1000])],
            'valor_unitario_compra' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->filled('id_item')) {
                return;
            }

            $item = Item::with('categoriaItem')
                ->where('id', $this->input('id_item'))
                ->whereNull('deleted_at')
                ->first();

            if (!$item) {
                return;
            }

            $isFilamento = $item->categoriaItem?->descricao === 'FILAMENTO';

            if ($isFilamento) {
                if (!$this->filled('gramatura_filamento')) {
                    $validator->errors()->add('gramatura_filamento', 'A gramatura do filamento é obrigatória.');
                }

                if ($this->filled('qtd_interna')) {
                    $validator->errors()->add('qtd_interna', 'A quantidade interna de filamentos é calculada automaticamente.');
                }

                return;
            }

            if ($this->filled('gramatura_filamento')) {
                $validator->errors()->add('gramatura_filamento', 'Gramatura de filamento não se aplica a este item.');
            }

            if (!$this->filled('qtd_interna')) {
                $validator->errors()->add('qtd_interna', 'A quantidade interna é obrigatória.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'id_compra.required'             => 'A compra é obrigatória.',
            'id_compra.exists'               => 'A compra informada não existe.',
            'id_item.required'               => 'O item é obrigatório.',
            'id_item.exists'                 => 'O item informado não existe.',
            'qtd_compra.required'            => 'A quantidade comprada é obrigatória.',
            'qtd_compra.gt'                  => 'A quantidade comprada deve ser maior que zero.',
            'qtd_interna.gt'                 => 'A quantidade interna deve ser maior que zero.',
            'gramatura_filamento.in'         => 'A gramatura do filamento deve ser 500 ou 1000.',
            'valor_unitario_compra.required' => 'O valor unitário da compra é obrigatório.',
            'valor_unitario_compra.min'      => 'O valor unitário da compra não pode ser negativo.',
        ];
    }
}
