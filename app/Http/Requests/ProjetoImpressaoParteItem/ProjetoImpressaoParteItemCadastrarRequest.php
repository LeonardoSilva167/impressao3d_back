<?php

namespace App\Http\Requests\ProjetoImpressaoParteItem;

use App\Http\Requests\ProjetoImpressaoParteItem\Concerns\PreparesProjetoImpressaoParteItemPayload;
use App\Http\Requests\ProjetoImpressaoParteItem\Concerns\ValidatesProjetoImpressaoParteItemCampos;
use Illuminate\Foundation\Http\FormRequest;

class ProjetoImpressaoParteItemCadastrarRequest extends FormRequest
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
        return $this->itemCamposRules();
    }

    public function messages(): array
    {
        return $this->itemCamposMessages();
    }
}
