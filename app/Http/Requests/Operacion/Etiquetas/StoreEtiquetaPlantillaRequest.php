<?php

namespace App\Http\Requests\Operacion\Etiquetas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEtiquetaPlantillaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'etp_nombre' => ['required', 'string', 'max:120'],
            'etp_descripcion' => ['nullable', 'string'],
            'etp_campos' => ['required', 'array', 'min:1'],
            'etp_campos.*' => [
                'string',
                Rule::in([
                    'nombre_producto',
                    'sku',
                    'codigo_barras',
                    'precio',
                    'linea',
                    'talla',
                    'color',
                    'unidad',
                    'cantidad',
                    'sucursal',
                    'fecha_recepcion',
                    'folio_recepcion',
                ]),
            ],
            'etp_estatus' => ['required', Rule::in(['activo', 'inactivo'])],
        ];
    }
}
