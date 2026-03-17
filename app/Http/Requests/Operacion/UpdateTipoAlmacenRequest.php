<?php

namespace App\Http\Requests\Operacion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTipoAlmacenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tipoAlmacenId = (int) $this->route('tipo_almacen');

        return [
            'tal_nombre' => ['required', 'string', 'max:80', Rule::unique('tbl_tipos_almacen_tal', 'tal_nombre')->ignore($tipoAlmacenId, 'tal_id')],
            'tal_descripcion' => ['nullable', 'string', 'max:220'],
            'tal_estatus' => ['required', Rule::in(['activo', 'inactivo'])],
        ];
    }
}
