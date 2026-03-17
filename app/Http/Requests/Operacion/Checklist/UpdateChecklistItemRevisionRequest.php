<?php

namespace App\Http\Requests\Operacion\Checklist;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChecklistItemRevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'chi_observacion' => trim((string) $this->input('chi_observacion')) ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'chi_estatus' => ['required', Rule::in(['pendiente', 'aprobado', 'observado', 'no_aplica'])],
            'chi_observacion' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'chi_estatus.required' => 'Debes seleccionar un estatus para el ítem.',
            'chi_estatus.in' => 'El estatus del ítem enviado no es válido.',
        ];
    }
}
