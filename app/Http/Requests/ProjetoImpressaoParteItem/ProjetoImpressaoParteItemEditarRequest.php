<?php

namespace App\Http\Requests\ProjetoImpressaoParteItem;

use App\Http\Requests\ProjetoImpressaoParteItem\Concerns\PreparesProjetoImpressaoParteItemPayload;
use App\Http\Requests\ProjetoImpressaoParteItem\Concerns\ValidatesProjetoImpressaoParteItemCampos;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjetoImpressaoParteItemEditarRequest extends FormRequest
{
    use PreparesProjetoImpressaoParteItemPayload, ValidatesProjetoImpressaoParteItemCampos;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareProjetoImpressaoParteItemPayload();
    }

    public function rules(): array
    {
        return array_merge([
            'id' => ['required', 'integer', Rule::exists('projetos_impressao_parte_itens', 'id')->whereNull('deleted_at')],
        ], $this->itemCamposRules());
    }

    public function messages(): array
    {
        return array_merge([
            'id.required' => 'O identificador do item é obrigatório.',
            'id.exists'   => 'O item informado não existe.',
        ], $this->itemCamposMessages());
    }
}
