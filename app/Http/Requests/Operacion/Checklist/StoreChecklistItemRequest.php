<?php

namespace App\Http\Requests\Operacion\Checklist;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChecklistItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'chi_titulo' => trim((string) $this->input('chi_titulo')),
            'chi_descripcion' => trim((string) $this->input('chi_descripcion')) ?: null,
            'chi_referencia_funcional' => trim((string) $this->input('chi_referencia_funcional')) ?: null,
            'chi_observacion' => trim((string) $this->input('chi_observacion')) ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'chi_titulo' => ['required', 'string', 'max:180'],
            'chi_descripcion' => ['nullable', 'string', 'max:5000'],
            'chi_referencia_funcional' => ['nullable', 'string', 'max:220'],
            'chi_estatus' => ['nullable', Rule::in(['pendiente', 'aprobado', 'observado', 'no_aplica'])],
            'chi_observacion' => ['nullable', 'string', 'max:5000'],
            'chi_orden' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
