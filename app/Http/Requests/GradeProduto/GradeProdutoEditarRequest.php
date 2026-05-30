<?php

namespace App\Http\Requests\GradeProduto;

use App\Http\Requests\GradeProduto\Concerns\NormalizesGradeProdutoCombinacoesInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GradeProdutoEditarRequest extends FormRequest
{
    use NormalizesGradeProdutoCombinacoesInput;
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id'              => ['required', 'integer', Rule::exists('grades_produtos', 'id')->whereNull('deleted_at')],
            'id_produto_base' => ['nullable', 'integer', Rule::exists('produtos_base', 'id')->whereNull('deleted_at')],
            'descricao'       => ['nullable', 'string', 'max:120'],
            'status'          => ['nullable', 'boolean'],
            'combinacoes'     => ['nullable', 'array', 'min:1'],
            'combinacoes.*.descricao' => ['nullable', 'string', 'max:120'],
            'combinacoes.*.partes' => ['required_with:combinacoes', 'array', 'min:1'],
            'combinacoes.*.partes.*.id_parte_projeto' => ['required_without:combinacoes.*.partes.*.id_parte', 'integer'],
            'combinacoes.*.partes.*.id_parte' => ['required_without:combinacoes.*.partes.*.id_parte_projeto', 'integer'],
            'combinacoes.*.partes.*.quantidade' => ['nullable', 'integer', 'min:1'],
            'gerar_produtos'  => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required'              => 'O identificador da grade é obrigatório.',
            'id.exists'                => 'A grade informada não existe.',
            'id_produto_base.exists'   => 'O produto base informado não existe.',
            'combinacoes.min'          => 'Cadastre ao menos uma combinação para a grade.',
            'combinacoes.*.partes.min' => 'Cada combinação deve possuir ao menos uma parte.',
        ];
    }
}
