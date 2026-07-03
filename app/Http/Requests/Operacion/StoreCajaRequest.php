<?php

namespace App\Http\Requests\Operacion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCajaRequest extends FormRequest
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
            'caj_retiro_umbral' => ['nullable', 'numeric', 'min:0'],
            'caj_estatus' => ['required', Rule::in(['activo', 'inactivo'])],
            'usuarios' => ['required', 'array', 'min:1'],
            'usuarios.*' => ['integer', 'distinct', 'exists:tbl_usuarios_usr,usr_id'],
        ];
    }

    public function messages(): array
    {
        return [
            'caj_scl_id.required' => 'La sucursal es obligatoria.',
            'caj_scl_id.exists' => 'La sucursal seleccionada no existe.',
            'caj_nombre.required' => 'El nombre de la caja es obligatorio.',
            'caj_retiro_umbral.min' => 'El umbral de retiro no puede ser negativo.',
            'caj_estatus.required' => 'El estatus es obligatorio.',
            'caj_estatus.in' => 'El estatus enviado no es válido.',
            'usuarios.required' => 'Debes asignar al menos un usuario a la caja.',
            'usuarios.min' => 'Debes asignar al menos un usuario a la caja.',
            'usuarios.*.exists' => 'Uno o más usuarios seleccionados no existen.',
        ];
    }
}
