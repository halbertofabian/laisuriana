<?php

namespace App\Http\Requests\Operacion\Inventario;

use Illuminate\Foundation\Http\FormRequest;

class CancelarMovimientoInventarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'min_motivo_texto' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'min_motivo_texto.required' => 'El motivo de cancelación es obligatorio.',
        ];
    }
}
