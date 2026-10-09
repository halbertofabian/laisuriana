<?php

namespace App\Http\Requests\Operacion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDatosFacturacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('cliente.crear') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->cli_rfc)) {
            $this->merge(['cli_rfc' => mb_strtoupper(trim($this->cli_rfc))]);
        }
    }

    public function rules(): array
    {
        return [
            'cli_razon_social' => ['required', 'string', 'max:180'],
            'cli_rfc' => ['required', 'string', 'regex:/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/u', Rule::unique('tbl_clientes_cli', 'cli_rfc')->where(fn ($query) => $query->where('cli_deleted', false))],
            'cli_regimen_fiscal' => ['required', Rule::in(array_keys(config('datos_facturacion.regimenes')))],
            'cli_uso_cfdi' => ['required', 'string', 'max:150'],
            'cli_cp' => ['required', 'string', 'regex:/^[0-9]{5}$/'],
            'cli_forma_pago' => ['required', 'string', 'max:100'],
            'cli_email' => ['nullable', 'email', 'max:140', Rule::unique('tbl_clientes_cli', 'cli_email')->where(fn ($query) => $query->where('cli_deleted', false))],
            'cli_telefono' => ['nullable', 'string', 'max:25'],
        ];
    }

    public function messages(): array
    {
        return [
            '*.required' => 'Este dato es obligatorio.',
            'cli_rfc.regex' => 'Captura un RFC válido de 12 o 13 caracteres.',
            'cli_rfc.unique' => 'Este RFC ya está registrado. Busca y selecciona al cliente en el POS.',
            'cli_cp.regex' => 'El código postal debe tener 5 dígitos.',
            'cli_regimen_fiscal.in' => 'Selecciona un régimen de la lista.',
            'cli_email.email' => 'Captura un correo válido.',
            'cli_email.unique' => 'Este correo ya está registrado en otro cliente.',
        ];
    }
}
