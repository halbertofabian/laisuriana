<?php

namespace App\Http\Requests\Operacion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDispositivoImpresoraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dip_nombre_dispositivo' => ['required', 'string', 'max:120'],
            'dip_tipo_conexion' => ['required', Rule::in(['red', 'usb'])],
            'dip_nombre_impresora' => ['required', 'string', 'max:160'],
            'dip_host' => [
                Rule::requiredIf(fn () => $this->string('dip_tipo_conexion')->toString() === 'red'),
                'nullable',
                'string',
                'max:190',
            ],
            'dip_puerto' => [
                Rule::requiredIf(fn () => $this->string('dip_tipo_conexion')->toString() === 'red'),
                'nullable',
                'integer',
                'between:1,65535',
            ],
            'dip_controlador' => ['nullable', 'string', 'max:80'],
            'dip_agent_url' => [
                Rule::requiredIf(fn () => $this->string('dip_tipo_conexion')->toString() === 'usb'),
                'nullable',
                'url',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'dip_nombre_dispositivo.required' => 'El nombre de referencia del dispositivo es obligatorio.',
            'dip_tipo_conexion.required' => 'Debes seleccionar el tipo de conexion.',
            'dip_tipo_conexion.in' => 'El tipo de conexion enviado no es valido.',
            'dip_nombre_impresora.required' => 'El nombre de la impresora es obligatorio.',
            'dip_host.required' => 'La IP o hostname es obligatorio para impresora por red.',
            'dip_puerto.required' => 'El puerto es obligatorio para impresora por red.',
            'dip_puerto.integer' => 'El puerto debe ser numerico.',
            'dip_puerto.between' => 'El puerto debe estar entre 1 y 65535.',
            'dip_agent_url.required' => 'La URL local del agente es obligatoria para impresora USB.',
            'dip_agent_url.url' => 'La URL del agente local no es valida.',
        ];
    }
}
