<?php

namespace App\Http\Requests\Operacion\Inventario;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class StoreInventarioInicialMasivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sucursalId = (int) $this->input('min_scl_id');

        return [
            'prd_id' => [
                'required',
                'integer',
                Rule::exists('tbl_productos_prd', 'prd_id')->where(fn ($query) => $query
                    ->where('prd_deleted', false)
                    ->whereNull('prd_deleted_at')
                    ->where('prd_estatus', 'activo')),
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
            'min_fecha_movimiento' => ['required', 'date'],
            'min_fecha_emision' => ['nullable', 'date'],
            'min_motivo_texto' => ['required', 'string', 'max:500'],
            'min_observaciones' => ['nullable', 'string', 'max:1500'],
            'min_documento_tipo' => ['required', Rule::in(['inventario_inicial', 'entrada_normal', 'compra_remision', 'compra_factura'])],
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
            'dominante_guardar_predeterminado' => ['nullable', 'boolean'],
            'lineas' => ['required', 'array', 'min:1'],
            'lineas.*.min_psk_id' => [
                'required',
                'integer',
                Rule::exists('tbl_producto_skus_psk', 'psk_id')->where(fn ($query) => $query
                    ->where('psk_deleted', false)
                    ->whereNull('psk_deleted_at')
                    ->where('psk_estatus', 'activo')),
            ],
            'lineas.*.min_cantidad' => ['nullable', 'numeric', 'min:0'],
            'lineas.*.min_precio_unitario' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $lineas = (array) $this->input('lineas', []);
            $alMenosUna = collect($lineas)
                ->contains(fn ($linea) => (float) ($linea['min_cantidad'] ?? 0) > 0);

            if (!$alMenosUna) {
                $validator->errors()->add('lineas', 'Debes capturar existencia mayor a cero en al menos una variante.');
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
            $descuentoTipo = (string) $this->input('min_descuento_tipo', 'ninguno');
            $descuentoValor = (float) $this->input('min_descuento_valor', 0);
            if ($descuentoTipo === 'porcentaje' && $descuentoValor > 100) {
                $validator->errors()->add('min_descuento_valor', 'Si el descuento es por porcentaje, no puede ser mayor a 100.');
            }

            $productoId = (int) $this->input('prd_id');
            $dominanteAtrId = (int) ($this->input('dominante_atr_id') ?? 0);
            if ($productoId > 0 && $dominanteAtrId > 0) {
                $existe = DB::table('tbl_producto_atributos_pat')
                    ->where('pat_prd_id', $productoId)
                    ->where('pat_atr_id', $dominanteAtrId)
                    ->where('pat_deleted', false)
                    ->whereNull('pat_deleted_at')
                    ->where('pat_estatus', 'activo')
                    ->exists();

                if (!$existe) {
                    $validator->errors()->add('dominante_atr_id', 'La variable dominante seleccionada no pertenece al producto.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'prd_id.required' => 'Debes seleccionar un producto base.',
            'prd_id.exists' => 'El producto seleccionado no está disponible.',
            'min_scl_id.required' => 'La sucursal es obligatoria.',
            'min_alm_id.required' => 'El almacén es obligatorio.',
            'min_alm_id.exists' => 'El almacén seleccionado no pertenece a la sucursal o no está activo.',
            'min_fecha_movimiento.required' => 'La fecha del movimiento es obligatoria.',
            'min_fecha_emision.date' => 'La fecha de emisión no tiene un formato válido.',
            'min_motivo_texto.required' => 'El motivo es obligatorio para la carga inicial.',
            'min_documento_tipo.required' => 'Debes seleccionar el tipo de entrada.',
            'min_prv_id.exists' => 'El proveedor seleccionado no está disponible.',
            'min_descuento_tipo.in' => 'Selecciona un tipo de descuento válido.',
            'dominante_atr_id.exists' => 'La variable dominante seleccionada no es válida.',
            'lineas.required' => 'Debes seleccionar un producto base y capturar existencias antes de guardar.',
            'lineas.*.min_psk_id.required' => 'La variante SKU es obligatoria.',
        ];
    }
}
