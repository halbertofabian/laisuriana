<?php

namespace App\Http\Requests\Reportes;

use Illuminate\Foundation\Http\FormRequest;

class ReportesFiltroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'desde' => ['nullable', 'date'], 'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'caja_id' => ['nullable', 'integer'], 'almacen_id' => ['nullable', 'integer'],
            'usuario_id' => ['nullable', 'integer'], 'q' => ['nullable', 'string', 'max:120'],
            'sucursal_id' => ['nullable', 'integer', 'exists:tbl_sucursales_scl,scl_id'],
            'grupo_id' => ['nullable', 'integer'],
            'estado' => ['nullable', 'in:borrador,calculado,cerrado,sin_configurar'],
        ];
    }
}
