<?php

namespace App\Http\Requests\Operacion\Inventario;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecepcionMercanciaDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sucursalId = (int) $this->input('min_scl_id');

        return [
            'rme_id' => ['nullable', 'integer'],
            'min_scl_id' => [
                'nullable',
                'integer',
                Rule::exists('tbl_sucursales_scl', 'scl_id')->where(fn ($query) => $query
                    ->where('scl_deleted', false)
                    ->whereNull('scl_deleted_at')
                    ->where('scl_estatus', 'activo')),
            ],
            'min_alm_id' => [
                'nullable',
                'integer',
                Rule::exists('tbl_almacenes_alm', 'alm_id')->where(fn ($query) => $query
                    ->where('alm_deleted', false)
                    ->whereNull('alm_deleted_at')
                    ->where('alm_estatus', 'activo')
                    ->when($sucursalId > 0, fn ($q) => $q->where('alm_scl_id', $sucursalId))),
            ],
            'min_fecha_movimiento' => ['nullable', 'date'],
            'min_fecha_emision' => ['nullable', 'date'],
            'min_motivo_texto' => ['nullable', 'string', 'max:500'],
            'min_observaciones' => ['nullable', 'string', 'max:1500'],
            'min_documento_tipo' => ['nullable', Rule::in(['inventario_inicial', 'entrada_normal', 'compra_remision', 'compra_factura'])],
            'min_documento_referencia' => ['nullable', 'string', 'max:120'],
            'min_prv_id' => [
                'nullable',
                'integer',
                Rule::exists('tbl_proveedores_prv', 'prv_id')->where(fn ($query) => $query
                    ->where('prv_deleted', false)
                    ->whereNull('prv_deleted_at')
                    ->where('prv_estatus', 'activo')),
            ],
            'min_descuento_tipo' => ['nullable', Rule::in(['ninguno', 'importe', 'porcentaje'])],
            'min_descuento_valor' => ['nullable', 'numeric', 'min:0'],
            'min_flete_total' => ['nullable', 'numeric', 'min:0'],
            'min_iva_porcentaje' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'dominante_atr_id' => [
                'nullable',
                'integer',
                Rule::exists('tbl_atributos_atr', 'atr_id')->where(fn ($query) => $query
                    ->where('atr_deleted', false)
                    ->whereNull('atr_deleted_at')
                    ->where('atr_estatus', 'activo')),
            ],
            'payload' => ['nullable', 'array'],
            'lineas' => ['nullable', 'array'],
            'lineas.*.prd_id' => ['nullable', 'integer'],
            'lineas.*.min_psk_id' => [
                'required_with:lineas',
                'integer',
                Rule::exists('tbl_producto_skus_psk', 'psk_id')->where(fn ($query) => $query
                    ->where('psk_deleted', false)
                    ->whereNull('psk_deleted_at')
                    ->where('psk_estatus', 'activo')),
            ],
            'lineas.*.min_cantidad' => ['nullable', 'integer', 'min:0'],
            'lineas.*.min_precio_unitario' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
