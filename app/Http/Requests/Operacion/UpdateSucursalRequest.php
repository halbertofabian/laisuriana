<?php

namespace App\Http\Requests\Operacion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSucursalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sucursalId = (int) $this->route('sucursal');

        return [
            'scl_nombre' => ['required', 'string', 'max:120', Rule::unique('tbl_sucursales_scl', 'scl_nombre')->ignore($sucursalId, 'scl_id')],
            'scl_estatus' => ['required', Rule::in(['activo', 'inactivo'])],
        ];
    }
}
