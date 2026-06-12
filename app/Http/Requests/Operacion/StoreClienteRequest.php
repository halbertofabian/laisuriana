<?php

namespace App\Http\Requests\Operacion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cli_nombre' => ['required', 'string', 'max:120'],
            'cli_apellido_paterno' => ['nullable', 'string', 'max:120'],
            'cli_apellido_materno' => ['nullable', 'string', 'max:120'],
            'cli_razon_social' => ['nullable', 'string', 'max:180'],
            'cli_fecha_nacimiento' => ['nullable', 'date'],
            'cli_telefono' => ['nullable', 'string', 'max:25'],
            'cli_whatsapp' => ['nullable', 'string', 'max:25'],
            'cli_email' => ['nullable', 'email', 'max:140', Rule::unique('tbl_clientes_cli', 'cli_email')->where(fn ($q) => $q->where('cli_deleted', false)->whereNull('cli_deleted_at'))],
            'cli_rfc' => ['nullable', 'string', 'max:20', Rule::unique('tbl_clientes_cli', 'cli_rfc')->where(fn ($q) => $q->where('cli_deleted', false)->whereNull('cli_deleted_at'))],
            'cli_curp' => ['nullable', 'string', 'max:25', Rule::unique('tbl_clientes_cli', 'cli_curp')->where(fn ($q) => $q->where('cli_deleted', false)->whereNull('cli_deleted_at'))],
            'cli_ine' => ['nullable', 'string', 'max:30'],
            'cli_cp' => ['nullable', 'string', 'max:10'],
            'cli_colonia' => ['nullable', 'string', 'max:150'],
            'cli_tipo_asentamiento' => ['nullable', 'string', 'max:80'],
            'cli_municipio' => ['nullable', 'string', 'max:120'],
            'cli_estado' => ['nullable', 'string', 'max:120'],
            'cli_ciudad' => ['nullable', 'string', 'max:120'],
            'cli_calle' => ['nullable', 'string', 'max:180'],
            'cli_num_ext' => ['nullable', 'string', 'max:30'],
            'cli_num_int' => ['nullable', 'string', 'max:30'],
            'cli_referencias' => ['nullable', 'string'],
            'cli_descuento_default' => ['nullable', 'integer', 'min:1', 'max:100'],
            'cli_estatus' => ['required', Rule::in(['activo', 'inactivo'])],
        ];
    }
}
