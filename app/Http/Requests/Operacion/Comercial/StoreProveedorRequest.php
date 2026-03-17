<?php

namespace App\Http\Requests\Operacion\Comercial;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreProveedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $numerosContacto = collect((array) $this->input('numeros_contacto', []))
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->merge([
            'prv_nombre_empresa' => trim((string) $this->input('prv_nombre_empresa')),
            'prv_nombre_asesor_ventas' => trim((string) $this->input('prv_nombre_asesor_ventas')) ?: null,
            'prv_categoria' => trim((string) $this->input('prv_categoria')) ?: null,
            'prv_razon_social' => trim((string) $this->input('prv_razon_social')) ?: null,
            'prv_rfc' => Str::upper(trim((string) $this->input('prv_rfc'))) ?: null,
            'prv_correo' => trim((string) $this->input('prv_correo')) ?: null,
            'prv_condiciones_pago' => trim((string) $this->input('prv_condiciones_pago')) ?: null,
            'prv_tiempo_respuesta' => trim((string) $this->input('prv_tiempo_respuesta')) ?: null,
            'numeros_contacto' => $numerosContacto,
        ]);
    }

    public function rules(): array
    {
        return [
            'prv_nombre_empresa' => [
                'required',
                'string',
                'max:180',
                Rule::unique('tbl_proveedores_prv', 'prv_nombre_empresa')->where(fn ($query) => $query->where('prv_deleted', false)),
            ],
            'prv_nombre_asesor_ventas' => ['nullable', 'string', 'max:180'],
            'prv_categoria' => ['nullable', 'string', 'max:120'],
            'prv_razon_social' => ['nullable', 'string', 'max:180'],
            'prv_rfc' => [
                'nullable',
                'string',
                'max:13',
                Rule::unique('tbl_proveedores_prv', 'prv_rfc')->where(fn ($query) => $query->where('prv_deleted', false)),
            ],
            'prv_correo' => ['nullable', 'email', 'max:160'],
            'prv_condiciones_pago' => ['nullable', 'string', 'max:220'],
            'prv_tiempo_respuesta' => ['nullable', 'string', 'max:120'],
            'prv_estatus' => ['nullable', Rule::in(['activo', 'inactivo'])],
            'numeros_contacto' => ['nullable', 'array'],
            'numeros_contacto.*' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\\-()\\s]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'prv_nombre_empresa.required' => 'El nombre de la empresa es obligatorio.',
            'prv_nombre_empresa.unique' => 'Ya existe un proveedor con ese nombre de empresa.',
            'prv_rfc.unique' => 'Ya existe un proveedor con ese RFC.',
            'prv_correo.email' => 'El correo debe tener un formato válido.',
            'prv_estatus.in' => 'El estatus enviado no es válido.',
            'numeros_contacto.array' => 'El formato de números de contacto no es válido.',
            'numeros_contacto.*.regex' => 'El número de contacto contiene caracteres no válidos.',
        ];
    }
}
