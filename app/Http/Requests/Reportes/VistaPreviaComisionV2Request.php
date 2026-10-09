<?php

namespace App\Http\Requests\Reportes;

/**
 * Vista previa de una configuración en captura: admite selecciones incompletas,
 * porque su objetivo es explicar qué falta antes de guardar.
 */
class VistaPreviaComisionV2Request extends GuardarConfiguracionComisionV2Request
{
    public function rules(): array
    {
        return [
            'periodo' => ['required', 'date_format:Y-m'],
            'almacen_ids' => ['nullable', 'array'],
            'almacen_ids.*' => ['integer'],
            'departamentos' => ['nullable', 'array'],
            'departamentos.*.habilitado' => ['boolean'],
            'departamentos.*.linea_ids' => ['nullable', 'array'],
            'departamentos.*.linea_ids.*' => ['integer'],
            'departamentos.*.incremento_meta' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'departamentos.*.meta_comun' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'departamentos.*.modo_meta' => ['nullable', 'in:historica,manual'],
            'vendedores' => ['nullable', 'array'],
            'vendedores.*.habilitado' => ['boolean'],
            'vendedores.*.departamento_id' => ['nullable', 'integer'],
            'vendedores.*.meta' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'vendedores.*.tasa' => ['nullable', 'numeric', 'in:0,0.1,0.2,0.3,0.4,0.5,0.6,0.7,0.8,0.9,1'],
        ];
    }

    public function withValidator($validator): void {}
}
