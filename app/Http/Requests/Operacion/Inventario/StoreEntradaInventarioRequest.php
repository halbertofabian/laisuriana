<?php

namespace App\Http\Requests\Operacion\Inventario;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEntradaInventarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sucursalId = (int) $this->input('min_scl_id');

        return [
            'min_psk_id' => [
                'required',
                'integer',
                Rule::exists('tbl_producto_skus_psk', 'psk_id')->where(fn ($query) => $query
                    ->where('psk_deleted', false)
                    ->whereNull('psk_deleted_at')
                    ->where('psk_estatus', 'activo')),
            ],
            'min_scl_id' => [
                'required',
                'integer',
                Rule::exists('tbl_sucursales_scl', 'scl_id')->where(fn ($query) => $query
                    ->where('scl_deleted', false)
                    ->whereNull('scl_deleted_at')
                    ->where('scl_estatus', 'activo')),
            ],
            'min_alm_id' => [
                'required',
                'integer',
                Rule::exists('tbl_almacenes_alm', 'alm_id')->where(fn ($query) => $query
                    ->where('alm_deleted', false)
                    ->whereNull('alm_deleted_at')
                    ->where('alm_estatus', 'activo')
                    ->where('alm_scl_id', $sucursalId)),
            ],
            'min_cantidad' => ['required', 'integer', 'gt:0'],
            'min_fecha_movimiento' => ['required', 'date'],
            'min_motivo_texto' => ['required', 'string', 'max:500'],
            'min_documento_referencia' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'min_psk_id.required' => 'El SKU es obligatorio.',
            'min_psk_id.exists' => 'El SKU seleccionado no está disponible.',
            'min_scl_id.required' => 'La sucursal es obligatoria.',
            'min_scl_id.exists' => 'La sucursal seleccionada no está disponible.',
            'min_alm_id.required' => 'El almacén es obligatorio.',
            'min_alm_id.exists' => 'El almacén seleccionado no pertenece a la sucursal o no está activo.',
            'min_cantidad.required' => 'La cantidad es obligatoria.',
            'min_cantidad.integer' => 'La cantidad debe ser un número entero.',
            'min_cantidad.gt' => 'La cantidad debe ser mayor a cero.',
            'min_fecha_movimiento.required' => 'La fecha del movimiento es obligatoria.',
            'min_motivo_texto.required' => 'El motivo de la entrada es obligatorio.',
        ];
    }
}
