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
            'pdp_cli_id' => ['nullable', 'integer', 'exists:tbl_clientes_cli,cli_id'],
            'pdp_observaciones' => ['nullable', 'string', 'max:1000'],
            'partidas' => ['required', 'array', 'min:1'],
            'partidas.*.ppd_psk_id' => ['required', 'integer', 'exists:tbl_producto_skus_psk,psk_id'],
            'partidas.*.ppd_cantidad' => ['required', 'numeric', 'min:0.01'],
            'partidas.*.ppd_descuento_tipo' => ['nullable', 'in:ninguno,porcentaje,importe'],
            'partidas.*.ppd_descuento_valor' => ['nullable', 'numeric', 'min:0'],
            'partidas.*.ppd_descuento_cantidad' => ['nullable', 'numeric', 'min:0'],
            'partidas.*.ppd_usr_id' => ['nullable', 'integer', 'exists:tbl_usuarios_usr,usr_id'],
        ];
    }
}
