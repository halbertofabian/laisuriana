<?php

namespace App\Http\Requests\Operacion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTipoAlmacenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tal_nombre' => ['required', 'string', 'max:80', Rule::unique('tbl_tipos_almacen_tal', 'tal_nombre')],
            'tal_descripcion' => ['nullable', 'string', 'max:220'],
            'tal_estatus' => ['required', Rule::in(['activo', 'inactivo'])],
        ];
    }

    public function messages(): array
    {
        return [
            'tal_nombre.required' => 'El nombre del tipo de almacén es obligatorio.',
            'tal_nombre.unique' => 'Ya existe un tipo de almacén con ese nombre.',
            'tal_estatus.required' => 'El estatus es obligatorio.',
            'tal_estatus.in' => 'El estatus enviado no es válido.',
        ];
    }
}
