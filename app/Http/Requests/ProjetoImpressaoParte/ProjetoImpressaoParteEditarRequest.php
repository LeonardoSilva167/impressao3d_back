<?php

namespace App\Http\Requests\ProjetoImpressaoParte;

use App\Http\Requests\ProjetoImpressaoParte\Concerns\PreparesProjetoImpressaoPartePayload;
use App\Http\Requests\ProjetoImpressaoParte\Concerns\ValidatesProjetoImpressaoParteCampos;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjetoImpressaoParteEditarRequest extends FormRequest
{
    use PreparesProjetoImpressaoPartePayload, ValidatesProjetoImpressaoParteCampos;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareProjetoImpressaoPartePayload();
    }

    public function rules(): array
    {
        return array_merge([
            'id' => ['required', 'integer', Rule::exists('projetos_impressao_partes', 'id')->whereNull('deleted_at')],
        ], $this->parteCamposRules());
    }

    public function messages(): array
    {
        return array_merge([
            'id.required' => 'O identificador da parte é obrigatório.',
            'id.exists'   => 'A parte informada não existe.',
        ], $this->parteCamposMessages());
    }
}
