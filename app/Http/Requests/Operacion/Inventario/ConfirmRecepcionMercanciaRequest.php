<?php

namespace App\Http\Requests\Operacion\Inventario;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ConfirmRecepcionMercanciaRequest extends FormRequest
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
            'min_fecha_movimiento' => ['nullable', 'date'],
            'min_fecha_emision' => ['nullable', 'date'],
            'min_motivo_texto' => ['required', 'string', 'max:500'],
            'min_observaciones' => ['nullable', 'string', 'max:1500'],
            'min_documento_tipo' => ['required', Rule::in(['entrada_normal', 'compra_remision', 'compra_factura'])],
            'min_documento_referencia' => ['nullable', 'string', 'max:120'],
            'confirm_password' => ['required', 'string', 'max:255'],
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
            'lineas' => ['required', 'array', 'min:1'],
            'lineas.*.prd_id' => [
                'required',
                'integer',
                Rule::exists('tbl_productos_prd', 'prd_id')->where(fn ($query) => $query
                    ->where('prd_deleted', false)
                    ->whereNull('prd_deleted_at')
                    ->where('prd_estatus', 'activo')),
            ],
            'lineas.*.min_psk_id' => [
                'required',
                'integer',
                Rule::exists('tbl_producto_skus_psk', 'psk_id')->where(fn ($query) => $query
                    ->where('psk_deleted', false)
                    ->whereNull('psk_deleted_at')
                    ->where('psk_estatus', 'activo')),
            ],
            'lineas.*.min_cantidad' => ['required', 'integer', 'min:1'],
            'lineas.*.min_precio_unitario' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $lineas = collect((array) $this->input('lineas', []));
            if ($lineas->isEmpty()) {
                return;
            }

            $usuario = $this->user();
            $password = (string) $this->input('confirm_password', '');
            if (!$usuario || !Hash::check($password, (string) $usuario->usr_password)) {
                $validator->errors()->add('confirm_password', 'La contraseña capturada no coincide con el usuario actual.');
            }

            $tipoDocumento = (string) $this->input('min_documento_tipo', '');
            if (in_array($tipoDocumento, ['compra_remision', 'compra_factura'], true) && trim((string) $this->input('min_documento_referencia', '')) === '') {
                $validator->errors()->add('min_documento_referencia', 'La referencia del documento es obligatoria cuando la entrada es por compra.');
            }
            if (in_array($tipoDocumento, ['compra_remision', 'compra_factura'], true) && trim((string) $this->input('min_fecha_emision', '')) === '') {
                $validator->errors()->add('min_fecha_emision', 'La fecha de emisión es obligatoria en compras con remisión o factura.');
            }
            if ($tipoDocumento === 'compra_factura' && (int) $this->input('min_prv_id', 0) <= 0) {
                $validator->errors()->add('min_prv_id', 'El proveedor es obligatorio cuando la entrada es compra con factura.');
            }

            if (in_array($tipoDocumento, ['compra_remision', 'compra_factura'], true)) {
                foreach ($lineas as $idx => $linea) {
                    if ((float) ($linea['min_precio_unitario'] ?? 0) <= 0) {
                        $validator->errors()->add("lineas.{$idx}.min_precio_unitario", 'Para compras con remisión o factura, el costo unitario debe ser mayor a cero.');
                    }
                }
            }

            $descuentoTipo = (string) $this->input('min_descuento_tipo', 'ninguno');
            $descuentoValor = (float) $this->input('min_descuento_valor', 0);
            if ($descuentoTipo === 'porcentaje' && $descuentoValor > 100) {
                $validator->errors()->add('min_descuento_valor', 'Si el descuento es por porcentaje, no puede ser mayor a 100.');
            }

            $skuIds = $lineas->pluck('min_psk_id')->map(fn ($id) => (int) $id)->unique()->values();
            $skuProducto = DB::table('tbl_producto_skus_psk')
                ->whereIn('psk_id', $skuIds->all())
                ->pluck('psk_prd_id', 'psk_id');

            foreach ($lineas as $idx => $linea) {
                $skuId = (int) ($linea['min_psk_id'] ?? 0);
                $productoId = (int) ($linea['prd_id'] ?? 0);
                if ($skuId > 0 && $productoId > 0 && (int) ($skuProducto[$skuId] ?? 0) !== $productoId) {
                    $validator->errors()->add("lineas.{$idx}.min_psk_id", 'El SKU no pertenece al producto indicado.');
                }
            }
        });
    }
}
