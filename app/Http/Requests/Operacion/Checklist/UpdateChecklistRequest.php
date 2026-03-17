<?php

namespace App\Http\Requests\Operacion\Checklist;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'chk_nombre' => trim((string) $this->input('chk_nombre')),
            'chk_referencia' => trim((string) $this->input('chk_referencia')) ?: null,
            'chk_observaciones' => trim((string) $this->input('chk_observaciones')) ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'chk_nombre' => ['required', 'string', 'max:180'],
            'chk_referencia' => ['nullable', 'string', 'max:180'],
            'chk_fecha' => ['required', 'date'],
            'chk_estatus_general' => ['required', Rule::in(['pendiente', 'en_revision', 'aprobado', 'observado'])],
            'chk_observaciones' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
