<?php

namespace App\Http\Requests\Operacion\Inventario;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListExistenciaMatrizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'draw' => ['nullable', 'integer', 'min:0'],
            'start' => ['nullable', 'integer', 'min:0'],
            'length' => ['nullable', 'integer', 'min:1', 'max:100'],
            'buscar' => ['nullable', 'string', 'max:120'],
            'prd_id' => [
                'nullable',
                'integer',
                Rule::exists('tbl_productos_prd', 'prd_id')->where(fn ($query) => $query
                    ->where('prd_deleted', false)
                    ->whereNull('prd_deleted_at')
                    ->where('prd_estatus', 'activo')),
            ],
            'prd_mrc_id' => [
                'nullable',
                'integer',
                Rule::exists('tbl_marcas_mrc', 'mrc_id')->where(fn ($query) => $query
                    ->where('mrc_deleted', false)
                    ->whereNull('mrc_deleted_at')
                    ->where('mrc_estatus', 'activo')),
            ],
            'prd_mdl_id' => [
                'nullable',
                'integer',
                Rule::exists('tbl_modelos_mdl', 'mdl_id')->where(fn ($query) => $query
                    ->where('mdl_deleted', false)
                    ->whereNull('mdl_deleted_at')
                    ->where('mdl_estatus', 'activo')),
            ],
            'prd_lna_id' => [
                'nullable',
                'integer',
                Rule::exists('tbl_lineas_lna', 'lna_id')->where(fn ($query) => $query
                    ->where('lna_deleted', false)
                    ->whereNull('lna_deleted_at')
                    ->where('lna_estatus', 'activo')),
            ],
            'prd_ctg_id' => [
                'nullable',
                'integer',
                Rule::exists('tbl_categorias_ctg', 'ctg_id')->where(fn ($query) => $query
                    ->where('ctg_deleted', false)
                    ->whereNull('ctg_deleted_at')
                    ->where('ctg_estatus', 'activo')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'prd_id.exists' => 'El producto seleccionado no está disponible.',
            'prd_mrc_id.exists' => 'La marca seleccionada no está disponible.',
            'prd_mdl_id.exists' => 'El modelo seleccionado no está disponible.',
            'prd_lna_id.exists' => 'La línea seleccionada no está disponible.',
            'prd_ctg_id.exists' => 'El concepto seleccionado no está disponible.',
        ];
    }
}
