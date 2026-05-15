<?php

namespace App\Http\Requests\Operacion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePosVentaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'almacen_id' => [
                'required',
                'integer',
                Rule::exists('tbl_almacenes_alm', 'alm_id')->where(fn ($q) => $q
                    ->where('alm_deleted', false)
                    ->whereNull('alm_deleted_at')
                    ->where('alm_estatus', 'activo')),
            ],
            'cliente_id' => ['nullable', 'integer', 'exists:tbl_clientes_cli,cli_id'],
            'pedido_id' => ['nullable', 'integer', 'exists:tbl_pedidos_piso_pdp,pdp_id'],
            'notas' => ['nullable', 'string', 'max:2000'],
            'descuento_global' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'metodo_pago' => ['required', Rule::in(['efectivo', 'tarjeta', 'mixto'])],
            'monto_efectivo' => ['nullable', 'numeric', 'min:0'],
            'monto_tarjeta' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.psk_id' => ['required', 'integer', 'exists:tbl_producto_skus_psk,psk_id'],
            'items.*.cantidad' => ['required', 'numeric', 'gt:0'],
            'items.*.precio' => ['nullable', 'numeric', 'min:0'],
            'items.*.descuento' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $metodo = (string) $this->input('metodo_pago');
            $efectivo = (float) $this->input('monto_efectivo', 0);
            $tarjeta = (float) $this->input('monto_tarjeta', 0);

            if ($metodo === 'efectivo' && $efectivo <= 0) {
                $validator->errors()->add('monto_efectivo', 'Captura el monto en efectivo.');
            }
            if ($metodo === 'tarjeta' && $tarjeta <= 0) {
                $validator->errors()->add('monto_tarjeta', 'Captura el monto en tarjeta.');
            }
            if ($metodo === 'mixto' && ($efectivo <= 0 || $tarjeta <= 0)) {
                $validator->errors()->add('metodo_pago', 'En pago mixto debes capturar efectivo y tarjeta.');
            }
        });
    }
}
