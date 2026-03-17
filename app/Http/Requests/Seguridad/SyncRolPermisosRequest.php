<?php

namespace App\Http\Requests\Seguridad;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncRolPermisosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
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
