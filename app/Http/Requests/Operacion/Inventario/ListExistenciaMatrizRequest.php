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
            'solo_disponibles' => ['nullable', 'boolean'],
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
            'prd_dsc_id' => [
                'nullable',
                'integer',
                Rule::exists('tbl_descripciones_dsc', 'dsc_id')->where(fn ($query) => $query
                    ->where('dsc_deleted', false)
                    ->whereNull('dsc_deleted_at')
                    ->where('dsc_estatus', 'activo')),
            ],
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
                    ->when($this->filled('min_scl_id'), fn ($q) => $q->where('alm_scl_id', (int) $this->input('min_scl_id')))),
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
            'prd_dsc_id.exists' => 'La descripción seleccionada no está disponible.',
            'min_scl_id.exists' => 'La sucursal seleccionada no está disponible.',
            'min_alm_id.exists' => 'El almacén seleccionado no pertenece a la sucursal o no está activo.',
        ];
    }
}
