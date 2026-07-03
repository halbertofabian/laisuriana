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
            'lineas.*.min_cantidad' => ['nullable', 'numeric', 'min:0'],
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

            $skuIds = $lineas->pluck('min_psk_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();
            if ($skuIds->isEmpty()) {
                return;
            }

            $tipos = \DB::table('tbl_producto_skus_psk as psk')
                ->join('tbl_productos_prd as prd', 'prd.prd_id', '=', 'psk.psk_prd_id')
                ->leftJoin('tbl_unidades_medida_umd as umd', 'umd.umd_id', '=', 'prd.prd_umd_id')
                ->whereIn('psk.psk_id', $skuIds->all())
                ->get([
                    'psk.psk_id',
                    'umd.umd_tipo_cantidad',
                    'umd.umd_codigo',
                    'umd.umd_nombre',
                ])
                ->keyBy('psk_id');

            foreach ($lineas as $idx => $linea) {
                $skuId = (int) ($linea['min_psk_id'] ?? 0);
                $cantidad = $linea['min_cantidad'] ?? null;
                if ($skuId <= 0 || $cantidad === null || $cantidad === '') {
                    continue;
                }
                $unidad = $tipos->get($skuId);
                if (!$this->unidadPermiteDecimal($unidad) && floor((float) $cantidad) != (float) $cantidad) {
                    $validator->errors()->add("lineas.{$idx}.min_cantidad", 'La cantidad debe ser entera para productos capturados por pieza.');
                }
            }
        });
    }

    private function unidadPermiteDecimal($unidad): bool
    {
        $tipo = $this->normalizarUnidadTexto($unidad->umd_tipo_cantidad ?? '');
        if ($tipo === 'decimal') return true;
        if ($tipo === 'entero') return false;

        $texto = trim($this->normalizarUnidadTexto(($unidad->umd_codigo ?? '') . ' ' . ($unidad->umd_nombre ?? '')));
        if (preg_match('/(^|\s)(m|mt|mts|metro|metros)(\s|$)/', $texto)) return true;
        if (preg_match('/(^|\s)(pza|pieza|piezas)(\s|$)/', $texto)) return false;

        return false;
    }

    private function normalizarUnidadTexto(string $valor): string
    {
        $valor = trim(mb_strtolower($valor));
        $replaced = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $valor);
        return $replaced === false ? $valor : $replaced;
    }
}
