<?php

namespace App\Http\Requests\Operacion\Etiquetas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerarEtiquetasRecepcionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'modo' => ['required', Rule::in(['unico', 'separado'])],
            'etiquetas_por_detalle' => ['nullable', 'array'],
            'etiquetas_por_detalle.*' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
