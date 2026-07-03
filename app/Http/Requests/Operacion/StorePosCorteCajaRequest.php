<?php

namespace App\Http\Requests\Operacion;

use Illuminate\Foundation\Http\FormRequest;

class StorePosCorteCajaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $denominaciones = (array) $this->input('denominaciones', []);
        $claves = ['1000', '500', '200', '100', '50', '20'];

        foreach ($claves as $clave) {
            $valor = $denominaciones[$clave] ?? 0;
            $denominaciones[$clave] = ($valor === '' || $valor === null) ? 0 : $valor;
        }

        $this->merge([
            'denominaciones' => $denominaciones,
            'autoriza_usr_id' => (int) $this->input('autoriza_usr_id', 0),
            'autoriza_usuario' => trim((string) $this->input('autoriza_usuario', '')),
            'cambio' => ($this->input('cambio') === '' || $this->input('cambio') === null)
                ? 0
                : $this->input('cambio'),
            'observaciones' => trim((string) $this->input('observaciones', '')) ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'denominaciones' => ['required', 'array'],
            'denominaciones.1000' => ['required', 'integer', 'min:0'],
            'denominaciones.500' => ['required', 'integer', 'min:0'],
            'denominaciones.200' => ['required', 'integer', 'min:0'],
            'denominaciones.100' => ['required', 'integer', 'min:0'],
            'denominaciones.50' => ['required', 'integer', 'min:0'],
            'denominaciones.20' => ['required', 'integer', 'min:0'],
            'cambio' => ['nullable', 'numeric', 'min:0'],
            'autoriza_usr_id' => ['required', 'integer', 'min:1', 'exists:tbl_usuarios_usr,usr_id'],
            'autoriza_password' => ['required', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'denominaciones.required' => 'Debes capturar el conteo de denominaciones.',
            'denominaciones.array' => 'Las denominaciones enviadas no son válidas.',
            'denominaciones.*.integer' => 'La cantidad de billetes debe ser un número entero.',
            'denominaciones.*.min' => 'La cantidad de billetes no puede ser negativa.',
            'cambio.numeric' => 'El cambio debe ser un monto válido.',
            'cambio.min' => 'El cambio no puede ser negativo.',
            'autoriza_usr_id.required' => 'Selecciona el usuario autorizado.',
            'autoriza_usr_id.integer' => 'El usuario autorizado no es válido.',
            'autoriza_usr_id.min' => 'Selecciona el usuario autorizado.',
            'autoriza_usr_id.exists' => 'El usuario autorizado seleccionado no existe.',
            'autoriza_password.required' => 'Captura la contraseña del usuario autorizado.',
        ];
    }
}
