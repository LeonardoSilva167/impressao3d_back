<?php

namespace App\Http\Requests\ProjetoImpressaoParte;

use App\Http\Requests\ProjetoImpressaoParte\Concerns\ValidatesProjetoImpressaoParteCampos;
use Illuminate\Foundation\Http\FormRequest;

class ProjetoImpressaoParteCadastrarRequest extends FormRequest
{
    use ValidatesProjetoImpressaoParteCampos;

    public function authorize(): bool
    {
        return true;
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
