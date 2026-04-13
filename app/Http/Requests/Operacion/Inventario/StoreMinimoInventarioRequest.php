<?php

namespace App\Http\Requests\Operacion\Inventario;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMinimoInventarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sucursalId = (int) $this->input('mni_scl_id');

        return [
            'mni_psk_id' => [
                'required',
                'integer',
                Rule::exists('tbl_producto_skus_psk', 'psk_id')->where(fn ($query) => $query
                    ->where('psk_deleted', false)
                    ->whereNull('psk_deleted_at')
                    ->where('psk_estatus', 'activo')),
            ],
            'mni_scl_id' => [
                'required',
                'integer',
                Rule::exists('tbl_sucursales_scl', 'scl_id')->where(fn ($query) => $query
                    ->where('scl_deleted', false)
                    ->whereNull('scl_deleted_at')
                    ->where('scl_estatus', 'activo')),
            ],
            'mni_alm_id' => [
                'required',
                'integer',
                Rule::exists('tbl_almacenes_alm', 'alm_id')->where(fn ($query) => $query
                    ->where('alm_deleted', false)
                    ->whereNull('alm_deleted_at')
                    ->where('alm_estatus', 'activo')
                    ->where('alm_scl_id', $sucursalId)),
            ],
            'mni_minimo' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'mni_psk_id.required' => 'El SKU es obligatorio.',
            'mni_psk_id.exists' => 'El SKU seleccionado no está disponible.',
            'mni_scl_id.required' => 'La sucursal es obligatoria.',
            'mni_scl_id.exists' => 'La sucursal seleccionada no está disponible.',
            'mni_alm_id.required' => 'El almacén es obligatorio.',
            'mni_alm_id.exists' => 'El almacén seleccionado no pertenece a la sucursal o no está activo.',
            'mni_minimo.required' => 'El stock mínimo es obligatorio.',
            'mni_minimo.min' => 'El stock mínimo no puede ser negativo.',
        ];
    }
}
