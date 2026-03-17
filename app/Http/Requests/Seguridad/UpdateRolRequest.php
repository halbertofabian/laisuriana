<?php

namespace App\Http\Requests\Seguridad;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rolId = $this->route('rol');

        return [
            'rol_nombre' => ['required', 'string', 'max:100', Rule::unique('tbl_roles_rol', 'rol_nombre')->ignore($rolId, 'rol_id')],
            'rol_descripcion' => ['nullable', 'string', 'max:220'],
            'rol_estatus' => ['required', Rule::in(['activo', 'inactivo'])],
            'permisos' => ['required', 'array', 'min:1'],
            'permisos.*' => [
                'integer',
                Rule::exists('tbl_permisos_prm', 'prm_id')->where(fn ($query) => $query
                    ->where('prm_deleted', false)
                    ->whereNull('prm_deleted_at')
                    ->where('prm_estatus', 'activo')),
            ],
        ];
    }
}
