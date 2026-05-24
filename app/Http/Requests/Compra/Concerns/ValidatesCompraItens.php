<?php

namespace App\Http\Requests\Compra\Concerns;

use Illuminate\Validation\Rule;

trait ValidatesCompraItens
{
    protected function compraItensRules(): array
    {
        return [
            'compra_itens'                         => ['required', 'array', 'min:1'],
            'compra_itens.*.id_item'               => ['required', 'integer', Rule::exists('itens', 'id')->whereNull('deleted_at')],
            'compra_itens.*.qtd_compra'            => ['required', 'numeric', 'gt:0'],
            'compra_itens.*.qtd_interna'           => ['required', 'numeric', 'gt:0'],
            'compra_itens.*.gramatura_filamento'   => ['nullable', 'integer', Rule::in([500, 1000])],
            'compra_itens.*.valor_unitario_compra' => ['required', 'numeric', 'min:0'],
            'compra_itens.*.valor_total'           => ['required', 'numeric', 'gt:0'],
        ];
    }

    protected function compraItensMessages(): array
    {
        return [
            'compra_itens.required'                         => 'Os itens da compra são obrigatórios.',
            'compra_itens.min'                              => 'A compra deve conter ao menos um item.',
            'compra_itens.*.id_item.required'               => 'O item é obrigatório.',
            'compra_itens.*.id_item.exists'                 => 'O item informado não existe.',
            'compra_itens.*.qtd_compra.required'            => 'A quantidade comprada é obrigatória.',
            'compra_itens.*.qtd_compra.gt'                  => 'A quantidade comprada deve ser maior que zero.',
            'compra_itens.*.qtd_interna.required'           => 'A quantidade interna é obrigatória.',
            'compra_itens.*.qtd_interna.gt'                 => 'A quantidade interna deve ser maior que zero.',
            'compra_itens.*.gramatura_filamento.in'         => 'A gramatura do filamento deve ser 500 ou 1000.',
            'compra_itens.*.valor_unitario_compra.required' => 'O valor unitário da compra é obrigatório.',
            'compra_itens.*.valor_unitario_compra.min'      => 'O valor unitário da compra não pode ser negativo.',
            'compra_itens.*.valor_total.required'           => 'O valor total do item é obrigatório.',
            'compra_itens.*.valor_total.gt'                 => 'O valor total do item deve ser maior que zero.',
        ];
    }
}
