<?php

namespace App\Http\Requests\Operacion\Inventario;

use Illuminate\Foundation\Http\FormRequest;

class CorregirMovimientoInventarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'min_motivo_texto' => ['required', 'string', 'max:500'],
            'nuevo.min_cantidad' => ['required', 'integer', 'gt:0'],
            'nuevo.min_documento_referencia' => ['nullable', 'string', 'max:120'],
            'nuevo.min_fecha_movimiento' => ['required', 'date'],
            'nuevo.min_motivo_texto' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'min_motivo_texto.required' => 'El motivo de la corrección es obligatorio.',
            'nuevo.min_cantidad.required' => 'La nueva cantidad es obligatoria.',
            'nuevo.min_cantidad.integer' => 'La nueva cantidad debe ser un número entero.',
            'nuevo.min_cantidad.gt' => 'La nueva cantidad debe ser mayor a cero.',
            'nuevo.min_fecha_movimiento.required' => 'La fecha corregida es obligatoria.',
            'nuevo.min_motivo_texto.required' => 'El motivo del nuevo movimiento es obligatorio.',
        ];
    }
}
