<?php

namespace App\Http\Requests\Operacion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAlmacenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'alm_scl_id' => [
                'required',
                'integer',
                Rule::exists('tbl_sucursales_scl', 'scl_id')->where(fn ($query) => $query
                    ->where('scl_deleted', false)
                    ->whereNull('scl_deleted_at')
                    ->where('scl_estatus', 'activo')),
            ],
            'alm_tal_id' => [
                'required',
                'integer',
                Rule::exists('tbl_tipos_almacen_tal', 'tal_id')->where(fn ($query) => $query
                    ->where('tal_deleted', false)
                    ->whereNull('tal_deleted_at')
                    ->where('tal_estatus', 'activo')),
            ],
            'alm_nombre' => ['required', 'string', 'max:120'],
            'alm_estatus' => ['required', Rule::in(['activo', 'inactivo'])],
        ];
    }

    public function messages(): array
    {
        return [
            'alm_scl_id.required' => 'La sucursal del almacén es obligatoria.',
            'alm_scl_id.exists' => 'La sucursal seleccionada no está disponible.',
            'alm_tal_id.required' => 'El tipo de almacén es obligatorio.',
            'alm_tal_id.exists' => 'El tipo de almacén seleccionado no está disponible.',
            'alm_nombre.required' => 'El nombre del almacén es obligatorio.',
            'alm_estatus.required' => 'El estatus es obligatorio.',
            'alm_estatus.in' => 'El estatus enviado no es válido.',
        ];
    }
}
