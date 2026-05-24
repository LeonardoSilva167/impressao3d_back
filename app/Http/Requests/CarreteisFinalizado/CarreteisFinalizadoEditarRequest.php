<?php

namespace App\Http\Requests\CarreteisFinalizado;

use App\Models\CarreteisFinalizado;
use App\Models\Filamento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CarreteisFinalizadoEditarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $payload = ['id' => (int) $this->id];

        if ($this->has('gramatura') && $this->gramatura !== null && $this->gramatura !== '') {
            $payload['gramatura'] = (int) $this->gramatura;
        }

        if ($this->has('quantidade') && $this->quantidade !== null && $this->quantidade !== '') {
            $payload['quantidade'] = (int) $this->quantidade;
        }

        if ($this->has('id_item') && $this->id_item !== null && $this->id_item !== '') {
            $payload['id_item'] = (int) $this->id_item;
        }

        if ($this->has('id_filamento') && $this->id_filamento !== null && $this->id_filamento !== '') {
            $payload['id_filamento'] = (int) $this->id_filamento;
        }

        $idItem = $payload['id_item'] ?? ($this->has('id_item') ? (int) $this->id_item : null);

        if (empty($idItem) && !empty($payload['id_filamento'])) {
            $filamento = Filamento::where('id', $payload['id_filamento'])
                ->whereNull('deleted_at')
                ->first();

            if ($filamento?->id_item) {
                $payload['id_item'] = (int) $filamento->id_item;
            }
        }

        $this->merge($payload);
    }

    public function rules(): array
    {
        return [
            'id'               => [
                'required',
                'integer',
                Rule::exists('carreteis_finalizados', 'id')->whereNull('deleted_at'),
            ],
            'id_item'          => [
                'required_without:id_filamento',
                'nullable',
                'integer',
                Rule::exists('itens', 'id')->whereNull('deleted_at'),
            ],
            'id_filamento'     => [
                'required_without:id_item',
                'nullable',
                'integer',
                Rule::exists('filamentos', 'id')->whereNull('deleted_at'),
            ],
            'gramatura'        => ['required', 'integer', Rule::in(CarreteisFinalizado::GRAMATURAS_VALIDAS)],
            'quantidade'       => ['required', 'integer', 'min:1'],
            'observacao'       => ['nullable', 'string', 'max:2000'],
            'data_finalizacao' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required'                   => 'O identificador é obrigatório.',
            'id.exists'                     => 'Registro de carretéis finalizados não encontrado.',
            'id_item.required_without'      => 'O item ou filamento é obrigatório.',
            'id_item.exists'                => 'O item informado não existe.',
            'id_filamento.required_without' => 'O item ou filamento é obrigatório.',
            'id_filamento.exists'           => 'O filamento informado não existe.',
            'gramatura.required'            => 'A gramatura é obrigatória.',
            'gramatura.in'                  => 'Gramatura inválida. Informe 500 ou 1000.',
            'quantidade.required'           => 'A quantidade é obrigatória.',
            'quantidade.min'                => 'A quantidade deve ser maior que zero.',
            'data_finalizacao.date'         => 'A data de finalização deve ser uma data válida.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (empty($this->id_item)) {
                $validator->errors()->add(
                    'id_filamento',
                    'Filamento sem item vinculado ou item não informado.'
                );
            }
        });
    }
}
