<?php

namespace App\Http\Requests\Operacion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePosCreditoCambioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'venta_origen_id' => ['required', 'integer', 'exists:tbl_pos_ventas_psv,psv_id'],
            'notas' => ['nullable', 'string', 'max:2000'],
            'devoluciones' => ['required', 'array', 'min:1'],
            'devoluciones.*.pvd_id' => ['required', 'integer', 'exists:tbl_pos_venta_detalle_pvd,pvd_id'],
            'devoluciones.*.cantidad' => ['required', 'numeric', 'gt:0'],
            'devoluciones.*.condicion' => ['nullable', Rule::in(['reventa', 'revision'])],
        ];
    }
}
