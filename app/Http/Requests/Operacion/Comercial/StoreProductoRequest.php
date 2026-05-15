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
            'prd_clave_sat' => ['nullable', 'string', 'max:20'],
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
            'prd_ctg_id' => ['nullable', 'integer', Rule::exists('tbl_categorias_ctg', 'ctg_id')],
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
            'corridas' => ['nullable', 'array'],
            'corridas.*.crc_nombre' => ['required_with:corridas', 'string', 'max:120'],
            'corridas.*.crc_atr_id' => ['required_with:corridas', 'integer', Rule::exists('tbl_atributos_atr', 'atr_id')],
            'corridas.*.crc_valor_ids' => ['required_with:corridas', 'array', 'min:1'],
            'corridas.*.crc_valor_ids.*' => ['integer', Rule::exists('tbl_valores_atributo_vat', 'vat_id')],
            'corridas.*.crc_precio_base' => ['required_with:corridas', 'numeric', 'min:0'],
            'corridas.*.crc_costo_base' => ['nullable', 'numeric', 'min:0'],
            'corridas.*.crc_stock_minimo' => ['required_with:corridas', 'integer', 'min:0'],
            'corridas.*.crc_stock_maximo' => ['required_with:corridas', 'integer', 'min:0'],
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
            'corridas.*.crc_nombre.required_with' => 'Cada corrida debe tener nombre.',
            'corridas.*.crc_atr_id.required_with' => 'Cada corrida debe indicar el atributo objetivo.',
            'corridas.*.crc_valor_ids.required_with' => 'Cada corrida debe seleccionar al menos un valor.',
            'corridas.*.crc_precio_base.required_with' => 'Cada corrida debe indicar precio base.',
            'corridas.*.crc_stock_minimo.required_with' => 'Cada corrida debe indicar stock mínimo.',
            'corridas.*.crc_stock_maximo.required_with' => 'Cada corrida debe indicar stock máximo.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (!$this->boolean('prd_imagen_reset')) {
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

            $corridas = collect($this->input('corridas', []))
                ->filter(fn ($item) => is_array($item))
                ->values();

            if ($corridas->isEmpty()) {
                $validator->errors()->add('corridas', 'Debes configurar al menos una corrida para producto variable.');
                return;
            }

            $valoresPorAtributo = [];
            foreach ($atributoIds as $atributoId) {
                $valoresPorAtributo[$atributoId] = collect($atributoValores->get((string) $atributoId, []))
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();
            }

            $usados = [];

            foreach ($corridas as $idx => $corrida) {
                $atrId = (int) ($corrida['crc_atr_id'] ?? 0);
                $nombre = trim((string) ($corrida['crc_nombre'] ?? ''));
                $stockMin = (int) ($corrida['crc_stock_minimo'] ?? 0);
                $stockMax = (int) ($corrida['crc_stock_maximo'] ?? 0);
                $valoresCorrida = collect($corrida['crc_valor_ids'] ?? [])
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();

                if ($nombre === '') {
                    $validator->errors()->add("corridas.{$idx}.crc_nombre", 'Cada corrida debe tener nombre.');
                }

                if (!$atributoIds->contains($atrId)) {
                    $validator->errors()->add("corridas.{$idx}.crc_atr_id", 'El atributo de la corrida debe formar parte de los atributos del producto.');
                    continue;
                }

                if ($stockMax < $stockMin) {
                    $validator->errors()->add("corridas.{$idx}.crc_stock_maximo", 'El stock máximo de la corrida debe ser mayor o igual al stock mínimo.');
                }

                $permitidos = $valoresPorAtributo[$atrId] ?? collect();
                foreach ($valoresCorrida as $valorId) {
                    if (!$permitidos->contains($valorId)) {
                        $validator->errors()->add("corridas.{$idx}.crc_valor_ids", 'La corrida incluye valores que no pertenecen al atributo seleccionado.');
                        continue;
                    }

                    if (isset($usados[$atrId][$valorId])) {
                        $validator->errors()->add("corridas.{$idx}.crc_valor_ids", 'Un mismo valor no puede repetirse en más de una corrida.');
                        continue;
                    }

                    $usados[$atrId][$valorId] = true;
                }
            }
        });
    }
}
