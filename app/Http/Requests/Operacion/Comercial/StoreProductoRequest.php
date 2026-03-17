<?php

namespace App\Http\Requests\Operacion\Comercial;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prd_codigo' => [
                'nullable',
                'string',
                'max:40',
                Rule::unique('tbl_productos_prd', 'prd_codigo')->where(fn ($query) => $query->where('prd_deleted', false)),
            ],
            'prd_codigo_barras' => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('tbl_productos_prd', 'prd_codigo_barras')->where(fn ($query) => $query->where('prd_deleted', false)),
            ],
            'prd_nombre' => [
                'required',
                'string',
                'max:180',
                Rule::unique('tbl_productos_prd', 'prd_nombre')->where(fn ($query) => $query->where('prd_deleted', false)),
            ],
            'prd_descripcion' => ['nullable', 'string', 'max:2000'],
            'prd_prv_id' => ['nullable', 'integer', Rule::exists('tbl_proveedores_prv', 'prv_id')],
            'prd_precio_base' => ['required', 'numeric', 'min:0'],
            'prd_costo' => ['nullable', 'numeric', 'min:0'],
            'prd_stock_minimo' => ['required', 'integer', 'min:0'],
            'prd_stock_maximo' => ['required', 'integer', 'gte:prd_stock_minimo'],
            'prd_mrc_id' => ['required', 'integer', Rule::exists('tbl_marcas_mrc', 'mrc_id')],
            'prd_mdl_id' => ['nullable', 'integer', Rule::exists('tbl_modelos_mdl', 'mdl_id')],
            'prd_lna_id' => ['required', 'integer', Rule::exists('tbl_lineas_lna', 'lna_id')],
            'prd_ctg_id' => ['required', 'integer', Rule::exists('tbl_categorias_ctg', 'ctg_id')],
            'prd_umd_id' => ['required', 'integer', Rule::exists('tbl_unidades_medida_umd', 'umd_id')],
            'prd_tipo' => ['required', Rule::in(['simple', 'variable'])],
            'prd_estatus' => ['required', Rule::in(['activo', 'inactivo'])],
            'prd_imagen_metodo' => ['nullable', Rule::in(['archivo', 'url', 'qr'])],
            'prd_imagen_archivo' => ['nullable', 'image', 'max:5120'],
            'prd_imagen_url' => ['nullable', 'url', 'max:500'],
            'prd_imagen_temp_token' => ['nullable', 'string', 'max:80'],
            'prd_imagen_reset' => ['nullable', 'boolean'],
            'atributo_ids' => ['array'],
            'atributo_ids.*' => ['integer', Rule::exists('tbl_atributos_atr', 'atr_id')],
            'atributo_valores' => ['array'],
            'atributo_valores.*' => ['array'],
            'atributo_valores.*.*' => ['integer', Rule::exists('tbl_valores_atributo_vat', 'vat_id')],
        ];
    }

    public function messages(): array
    {
        return [
            'prd_nombre.required' => 'El nombre del producto es obligatorio.',
            'prd_nombre.unique' => 'Ya existe un producto con ese nombre.',
            'prd_codigo_barras.unique' => 'Ya existe un producto con ese código de barras.',
            'prd_precio_base.required' => 'El precio base es obligatorio.',
            'prd_precio_base.numeric' => 'El precio base debe ser numérico.',
            'prd_costo.numeric' => 'El costo debe ser numérico.',
            'prd_stock_minimo.required' => 'El stock mínimo base es obligatorio.',
            'prd_stock_maximo.required' => 'El stock máximo base es obligatorio.',
            'prd_stock_maximo.gte' => 'El stock máximo debe ser mayor o igual al stock mínimo.',
            'prd_tipo.required' => 'Debes seleccionar si el producto es simple o variable.',
            'prd_tipo.in' => 'El tipo de producto enviado no es válido.',
            'prd_imagen_archivo.image' => 'La imagen general debe ser un archivo de imagen válido.',
            'prd_imagen_archivo.max' => 'La imagen general no debe superar los 5 MB.',
            'prd_imagen_url.url' => 'El enlace de imagen debe ser una URL válida.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $metodoImagen = $this->input('prd_imagen_metodo');

            if ($metodoImagen === 'archivo' && !$this->hasFile('prd_imagen_archivo')) {
                $validator->errors()->add('prd_imagen_archivo', 'Debes seleccionar una imagen desde el dispositivo.');
            }

            if ($metodoImagen === 'url' && blank($this->input('prd_imagen_url'))) {
                $validator->errors()->add('prd_imagen_url', 'Debes capturar el enlace externo de la imagen.');
            }

            if ($metodoImagen === 'qr' && blank($this->input('prd_imagen_temp_token'))) {
                $validator->errors()->add('prd_imagen_temp_token', 'Debes cargar la imagen desde el celular antes de guardar.');
            }

            if ($this->input('prd_tipo') !== 'variable') {
                return;
            }

            $atributoIds = collect($this->input('atributo_ids', []))
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            if ($atributoIds->isEmpty()) {
                $validator->errors()->add('atributo_ids', 'Debes seleccionar al menos un atributo para un producto variable.');
                return;
            }

            $atributoValores = collect($this->input('atributo_valores', []));

            foreach ($atributoIds as $atributoId) {
                $valores = collect($atributoValores->get((string) $atributoId, []))
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();

                if ($valores->isEmpty()) {
                    $validator->errors()->add('atributo_valores', 'Cada atributo seleccionado debe tener al menos un valor para generar corridas.');
                    break;
                }
            }
        });
    }
}
