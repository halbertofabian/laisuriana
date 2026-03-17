<?php

namespace App\Http\Requests\Seguridad;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'usr_nombre' => ['required', 'string', 'max:160'],
            'usr_usuario' => ['required', 'string', 'max:60', Rule::unique('tbl_usuarios_usr', 'usr_usuario')],
            'usr_email' => ['nullable', 'email', 'max:160'],
            'usr_password' => ['required', 'string', 'min:8', 'max:255'],
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

    public function messages(): array
    {
        return [
            'usr_nombre.required' => 'El nombre del usuario es obligatorio.',
            'usr_usuario.required' => 'El identificador de acceso (usuario) es obligatorio.',
            'usr_usuario.unique' => 'El usuario ya existe.',
            'usr_password.required' => 'La contraseña es obligatoria.',
            'usr_password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'roles.required' => 'Debes asignar al menos un rol.',
            'sucursales.required' => 'Debes asignar al menos una sucursal.',
        ];
    }
}
