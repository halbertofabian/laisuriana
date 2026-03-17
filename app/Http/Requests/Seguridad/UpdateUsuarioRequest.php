<?php

namespace App\Http\Requests\Seguridad;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $usuarioId = $this->route('usuario');

        return [
            'usr_nombre' => ['required', 'string', 'max:160'],
            'usr_usuario' => ['required', 'string', 'max:60', Rule::unique('tbl_usuarios_usr', 'usr_usuario')->ignore($usuarioId, 'usr_id')],
            'usr_email' => ['nullable', 'email', 'max:160'],
            'usr_password' => ['nullable', 'string', 'min:8', 'max:255'],
            'usr_estatus' => ['required', Rule::in(['activo', 'inactivo'])],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [
                'integer',
                Rule::exists('tbl_roles_rol', 'rol_id')->where(fn ($query) => $query
                    ->where('rol_deleted', false)
                    ->whereNull('rol_deleted_at')
                    ->where('rol_estatus', 'activo')),
            ],
            'sucursales' => ['required', 'array', 'min:1'],
            'sucursales.*' => [
                'integer',
                Rule::exists('tbl_sucursales_scl', 'scl_id')->where(fn ($query) => $query
                    ->where('scl_deleted', false)
                    ->whereNull('scl_deleted_at')
                    ->where('scl_estatus', 'activo')),
            ],
            'usc_scl_predeterminada' => ['nullable', 'integer'],
        ];
    }
}
