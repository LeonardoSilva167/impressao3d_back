<?php

namespace App\Http\Requests\Estoque;

use App\Models\MovimentacaoEstoque;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EstoqueConsumirRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_filamento'      => ['required', 'integer', Rule::exists('filamentos', 'id')->whereNull('deleted_at')],
            'qtd'               => ['required', 'numeric', 'gt:0'],
            'tipo_movimentacao' => ['nullable', 'string', Rule::in(MovimentacaoEstoque::TIPOS_SAIDA)],
            'observacao'        => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_filamento.required' => 'O filamento é obrigatório.',
            'id_filamento.exists'   => 'O filamento informado não existe.',
            'qtd.required'          => 'A quantidade consumida é obrigatória.',
            'qtd.gt'                => 'A quantidade consumida deve ser maior que zero.',
            'tipo_movimentacao.in'  => 'Tipo de movimentação inválido.',
        ];
    }
}
