<?php

namespace App\Http\Requests\Licitacoes;

use Illuminate\Foundation\Http\FormRequest;

class LicitacoesListaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'data_limite_proposta_inicio' => 'nullable|date_format:Y-m-d',
            'data_limite_proposta_final' => 'nullable|date_format:Y-m-d',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $dataInicio = $this->input('data_limite_proposta_inicio');
            $dataFim = $this->input('data_limite_proposta_final');

            if ($dataInicio > $dataFim) {
                $validator->errors()->add('data_limite_proposta_inicio', 'Data Limíte Início deve ser maior ou igual à Data Limíte Final.');
            }
        });
    }

    public function messages(): array
    {
        return [
            // 
        ];
    }
}
