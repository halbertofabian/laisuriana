<?php

namespace App\Http\Requests\Operacion\Comercial;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateValorAtributoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vat_atr_id' => ['required', 'integer', Rule::exists('tbl_atributos_atr', 'atr_id')],
            'vat_valor' => ['required', 'string', 'max:120'],
            'vat_clave' => ['nullable', 'string', 'max:40'],
            'vat_estatus' => ['required', Rule::in(['activo', 'inactivo'])],
        ];
    }
}
