<?php

namespace App\Http\Requests\ProjetoImpressaoParte;

use App\Http\Requests\ProjetoImpressaoParte\Concerns\PreparesProjetoImpressaoPartePayload;
use App\Http\Requests\ProjetoImpressaoParte\Concerns\ValidatesProjetoImpressaoParteCampos;
use Illuminate\Foundation\Http\FormRequest;

class ProjetoImpressaoParteCadastrarRequest extends FormRequest
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
        return $this->parteCamposRules();
    }

    public function messages(): array
    {
        return $this->parteCamposMessages();
    }
}
