<?php

namespace App\Http\Requests\ProjetoImpressaoParte\Concerns;

use Illuminate\Validation\Rule;

trait ValidatesProjetoImpressaoParteCampos
{
    protected function parteCamposRules(): array
    {
        return [
            'id_projeto_impressao' => ['required', 'integer', Rule::exists('projetos_impressao', 'id')->whereNull('deleted_at')],
            'nome_parte'           => ['required', 'string', 'max:255'],
        ];
    }

    protected function parteCamposMessages(): array
    {
        return [
            'id_projeto_impressao.required' => 'O projeto de impressão é obrigatório.',
            'id_projeto_impressao.exists'   => 'O projeto de impressão informado não existe.',
            'nome_parte.required'           => 'O nome da parte é obrigatório.',
        ];
    }
}
