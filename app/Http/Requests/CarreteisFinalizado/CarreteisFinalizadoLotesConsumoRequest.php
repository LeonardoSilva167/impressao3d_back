<?php

namespace App\Http\Requests\CarreteisFinalizado;

use App\Models\CarreteisFinalizado;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CarreteisFinalizadoLotesConsumoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $payload = [];

        if ($this->query('id_item') !== null && $this->query('id_item') !== '') {
            $payload['id_item'] = (int) $this->query('id_item');
        }

        if ($this->query('gramatura') !== null && $this->query('gramatura') !== '') {
            $payload['gramatura'] = (int) $this->query('gramatura');
        }

        if ($this->query('quantidade') !== null && $this->query('quantidade') !== '') {
            $payload['quantidade'] = (int) $this->query('quantidade');
        }

        if (!empty($payload)) {
            $this->merge($payload);
        }
    }

    public function rules(): array
    {
        return [
            'id_item'    => [
                'required',
                'integer',
                Rule::exists('itens', 'id')->whereNull('deleted_at'),
            ],
            'gramatura'  => ['required', 'integer', Rule::in(CarreteisFinalizado::GRAMATURAS_VALIDAS)],
            'quantidade' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_item.required'    => 'O item é obrigatório.',
            'id_item.exists'      => 'O item informado não existe.',
            'gramatura.required'  => 'A gramatura é obrigatória.',
            'gramatura.in'        => 'Gramatura inválida. Informe 500 ou 1000.',
            'quantidade.required' => 'A quantidade é obrigatória.',
            'quantidade.min'      => 'A quantidade deve ser maior que zero.',
        ];
    }
}
