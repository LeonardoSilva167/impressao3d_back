<?php

namespace App\Http\Requests\GradeProduto;

use App\Http\Requests\GradeProduto\Concerns\NormalizesGradeProdutoCombinacoesInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GradeProdutoCadastrarRequest extends FormRequest
{
    use NormalizesGradeProdutoCombinacoesInput;
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_produto_base' => ['required', 'integer', Rule::exists('produtos_base', 'id')->whereNull('deleted_at')],
            'descricao'       => ['required', 'string', 'max:120'],
            'status'          => ['nullable', 'boolean'],
            'combinacoes'     => ['required', 'array', 'min:1'],
            'combinacoes.*.descricao' => ['nullable', 'string', 'max:120'],
            'combinacoes.*.partes' => ['required', 'array', 'min:1'],
            'combinacoes.*.partes.*.id_parte_projeto' => ['required_without:combinacoes.*.partes.*.id_parte', 'integer'],
            'combinacoes.*.partes.*.id_parte' => ['required_without:combinacoes.*.partes.*.id_parte_projeto', 'integer'],
            'combinacoes.*.partes.*.quantidade' => ['nullable', 'integer', 'min:1'],
            'gerar_produtos'  => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_produto_base.required' => 'O produto base é obrigatório.',
            'id_produto_base.exists'   => 'O produto base informado não existe.',
            'descricao.required'       => 'A descrição da grade é obrigatória.',
            'combinacoes.required'     => 'Cadastre ao menos uma combinação para a grade.',
            'combinacoes.min'          => 'Cadastre ao menos uma combinação para a grade.',
            'combinacoes.*.partes.required' => 'Cada combinação deve possuir ao menos uma parte.',
            'combinacoes.*.partes.min'      => 'Cada combinação deve possuir ao menos uma parte.',
        ];
    }
}
