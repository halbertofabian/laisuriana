<?php

namespace App\Http\Requests\Operacion\Comercial;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAtributoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = (int) $this->route('atributo');

        return [
            'atr_nombre' => [
                'required',
                'string',
                'max:120',
                Rule::unique('tbl_atributos_atr', 'atr_nombre')
                    ->ignore($id, 'atr_id')
                    ->where(fn ($query) => $query->where('atr_deleted', false)),
            ],
            'atr_clave' => [
                'nullable',
                'string',
                'max:40',
                Rule::unique('tbl_atributos_atr', 'atr_clave')
                    ->ignore($id, 'atr_id')
                    ->where(fn ($query) => $query->where('atr_deleted', false)),
            ],
            'atr_tipo' => ['nullable', 'string', 'max:40'],
            'atr_estatus' => ['required', Rule::in(['activo', 'inactivo'])],
        ];
    }
}
