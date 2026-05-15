<?php

namespace App\Http\Requests\Operacion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCajaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'caj_scl_id' => ['required', 'integer', 'exists:tbl_sucursales_scl,scl_id'],
            'caj_alm_id' => ['nullable', 'integer', 'exists:tbl_almacenes_alm,alm_id'],
            'caj_nombre' => ['required', 'string', 'max:120'],
            'caj_estatus' => ['required', Rule::in(['activo', 'inactivo'])],
            'usuarios' => ['required', 'array', 'min:1'],
            'usuarios.*' => ['integer', 'distinct', 'exists:tbl_usuarios_usr,usr_id'],
        ];
    }
}
