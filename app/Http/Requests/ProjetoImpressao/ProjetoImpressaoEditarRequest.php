<?php

namespace App\Http\Requests\ProjetoImpressao;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjetoImpressaoEditarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id'                    => ['required', 'integer', Rule::exists('projetos_impressao', 'id')->whereNull('deleted_at')],
            'url_projeto'           => ['required', 'string'],
            'nome_original_projeto' => ['required', 'string', 'max:255'],
            'codigo_projeto'        => [
                'required',
                'string',
                'max:100',
                Rule::unique('projetos_impressao', 'codigo_projeto')
                    ->ignore($this->input('id'))
                    ->whereNull('deleted_at'),
            ],
            'descricao_projeto'     => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required'                    => 'O identificador do projeto é obrigatório.',
            'id.exists'                      => 'O projeto informado não existe.',
            'url_projeto.required'           => 'A URL do projeto é obrigatória.',
            'nome_original_projeto.required' => 'O nome original do projeto é obrigatório.',
            'codigo_projeto.required'        => 'O código do projeto é obrigatório.',
            'codigo_projeto.unique'          => 'Já existe um projeto com este código.',
            'descricao_projeto.required'     => 'A descrição do projeto é obrigatória.',
        ];
    }
}
