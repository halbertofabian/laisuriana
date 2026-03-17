<?php

namespace App\Http\Requests\Operacion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSucursalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'scl_nombre' => ['required', 'string', 'max:120', Rule::unique('tbl_sucursales_scl', 'scl_nombre')],
            'scl_estatus' => ['required', Rule::in(['activo', 'inactivo'])],
        ];
    }

    public function messages(): array
    {
        return [
            'scl_nombre.required' => 'El nombre de la sucursal es obligatorio.',
            'scl_nombre.unique' => 'Ya existe una sucursal con ese nombre.',
            'scl_estatus.required' => 'El estatus es obligatorio.',
            'scl_estatus.in' => 'El estatus enviado no es válido.',
        ];
    }
}
