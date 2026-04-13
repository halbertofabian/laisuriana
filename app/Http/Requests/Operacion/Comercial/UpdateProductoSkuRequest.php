<?php

namespace App\Http\Requests\Operacion\Comercial;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductoSkuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = (int) $this->route('sku');

        return [
            'psk_prd_id' => ['required', 'integer', Rule::exists('tbl_productos_prd', 'prd_id')],
            'psk_codigo' => ['required', 'string', 'max:60'],
            'psk_codigo_barras' => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('tbl_producto_skus_psk', 'psk_codigo_barras')
                    ->ignore($id, 'psk_id')
                    ->where(fn ($query) => $query->where('psk_deleted', false)),
            ],
            'psk_nombre' => ['nullable', 'string', 'max:180'],
            'psk_stock_minimo' => ['nullable', 'integer', 'min:0'],
            'psk_stock_maximo' => ['nullable', 'integer', 'gte:psk_stock_minimo'],
            'psk_estatus' => ['required', Rule::in(['activo', 'inactivo'])],
            'valor_atributo_ids' => ['nullable', 'array', 'min:1'],
            'valor_atributo_ids.*' => ['integer', Rule::exists('tbl_valores_atributo_vat', 'vat_id')],
        ];
    }
}
