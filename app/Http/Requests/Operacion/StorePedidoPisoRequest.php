<?php

namespace App\Http\Requests\Operacion;

use Illuminate\Foundation\Http\FormRequest;

class StorePedidoPisoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pdp_scl_id' => ['required', 'integer', 'exists:tbl_sucursales_scl,scl_id'],
            'pdp_alm_id' => ['required', 'integer', 'exists:tbl_almacenes_alm,alm_id'],
            'pdp_observaciones' => ['nullable', 'string', 'max:1000'],
            'partidas' => ['required', 'array', 'min:1'],
            'partidas.*.ppd_psk_id' => ['required', 'integer', 'exists:tbl_producto_skus_psk,psk_id'],
            'partidas.*.ppd_cantidad' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
