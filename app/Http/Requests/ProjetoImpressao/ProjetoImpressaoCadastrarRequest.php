<?php

namespace App\Http\Requests\ProjetoImpressao;

use App\Http\Requests\ProjetoImpressao\Concerns\PreparesProjetoImpressaoPayload;
use App\Http\Requests\ProjetoImpressao\Concerns\ValidatesProjetoImpressaoCores;
use App\Services\ProjetoImpressaoParte\ProjetoImpressaoParteConfig;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjetoImpressaoCadastrarRequest extends FormRequest
{
    use PreparesProjetoImpressaoPayload, ValidatesProjetoImpressaoCores;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareProjetoImpressaoPayload();
    }

    public function rules(): array
    {
        return array_merge([
            'url_projeto'           => ['required', 'string'],
            'nome_original_projeto' => ['required', 'string', 'max:255'],
            'codigo_projeto'        => ['required', 'string', 'max:100', Rule::unique('projetos_impressao', 'codigo_projeto')->whereNull('deleted_at')],
            'descricao_projeto'     => ['required', 'string'],
            'bico_padrao'           => ['required', Rule::in(ProjetoImpressaoParteConfig::BICOS_PADRAO)],
        ], $this->projetoCamposRules(), $this->projetoCoresRules());
    }

    public function messages(): array
    {
        return array_merge([
            'url_projeto.required'           => 'A URL do projeto é obrigatória.',
            'nome_original_projeto.required' => 'O nome original do projeto é obrigatório.',
            'codigo_projeto.required'        => 'O código do projeto é obrigatório.',
            'codigo_projeto.unique'          => 'Já existe um projeto com este código.',
            'descricao_projeto.required'     => 'A descrição do projeto é obrigatória.',
            'bico_padrao.required'           => 'O bico padrão é obrigatório.',
            'bico_padrao.in'                 => 'O bico padrão informado é inválido.',
        ], $this->projetoCamposMessages(), $this->projetoCoresMessages());
    }
}
