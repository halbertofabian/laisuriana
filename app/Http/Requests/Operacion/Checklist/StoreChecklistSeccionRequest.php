<?php

namespace App\Http\Requests\Operacion\Checklist;

use Illuminate\Foundation\Http\FormRequest;

class StoreChecklistSeccionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'chs_titulo' => trim((string) $this->input('chs_titulo')),
            'chs_descripcion' => trim((string) $this->input('chs_descripcion')) ?: null,
            'chs_observacion' => trim((string) $this->input('chs_observacion')) ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'chs_titulo' => ['required', 'string', 'max:160'],
            'chs_descripcion' => ['nullable', 'string', 'max:5000'],
            'chs_observacion' => ['nullable', 'string', 'max:5000'],
            'chs_orden' => ['nullable', 'integer', 'min:1'],
            'chs_estatus' => ['nullable', 'in:activo,inactivo'],
        ];
    }
}
