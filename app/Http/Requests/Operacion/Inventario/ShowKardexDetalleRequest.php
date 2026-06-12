<?php

namespace App\Http\Requests\Operacion\Inventario;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowKardexDetalleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'periodo' => ['nullable', Rule::in(['este_mes', 'ultimos_3_meses', 'este_anio', 'ultimos_3_anios', 'rango'])],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date'],
            'back_prd_mrc_id' => ['nullable', 'integer'],
            'back_prd_mdl_id' => ['nullable', 'integer'],
            'back_prd_lna_id' => ['nullable', 'integer'],
            'back_prd_ctg_id' => ['nullable', 'integer'],
            'back_prd_id' => ['nullable', 'integer'],
            'back_prd_text' => ['nullable', 'string', 'max:180'],
            'back_buscar' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'periodo.in' => 'El periodo solicitado no es válido.',
            'fecha_inicio.date' => 'La fecha inicial no es válida.',
            'fecha_fin.date' => 'La fecha final no es válida.',
        ];
    }
}
