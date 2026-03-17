<?php

namespace App\Http\Requests\Operacion\Comercial;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerarEtiquetaSkuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'formato' => $this->input('formato', config('etiquetado.formato_default', 'zebra_50x30')),
            'copias' => (int) $this->input('copias', 1),
            'usar_configuracion_manual' => filter_var($this->input('usar_configuracion_manual', false), FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    public function rules(): array
    {
        $formatos = array_keys(config('etiquetado.formatos', []));

        return [
            'formato' => ['required', 'string', Rule::in($formatos)],
            'copias' => ['required', 'integer', 'min:1', 'max:50'],
            'usar_configuracion_manual' => ['nullable', 'boolean'],
            'width_mm' => ['nullable', 'numeric', 'min:20', 'max:120'],
            'height_mm' => ['nullable', 'numeric', 'min:10', 'max:120'],
            'margin_left_mm' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'margin_right_mm' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'margin_top_mm' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'margin_bottom_mm' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'barcode_height_mm' => ['nullable', 'numeric', 'min:4', 'max:25'],
            'barcode_xres' => ['nullable', 'numeric', 'min:0.2', 'max:0.8'],
        ];
    }

    public function messages(): array
    {
        return [
            'formato.required' => 'El formato de etiqueta es obligatorio.',
            'formato.in' => 'El formato de etiqueta enviado no es válido.',
            'copias.required' => 'La cantidad de copias es obligatoria.',
            'copias.integer' => 'La cantidad de copias debe ser un número entero.',
            'copias.min' => 'La cantidad mínima de copias es 1.',
            'copias.max' => 'La cantidad máxima de copias permitida es 50.',
            'width_mm.min' => 'El ancho mínimo de etiqueta es 20 mm.',
            'width_mm.max' => 'El ancho máximo de etiqueta es 120 mm.',
            'height_mm.min' => 'El alto mínimo de etiqueta es 10 mm.',
            'height_mm.max' => 'El alto máximo de etiqueta es 120 mm.',
            'margin_left_mm.max' => 'El margen izquierdo máximo es 10 mm.',
            'margin_right_mm.max' => 'El margen derecho máximo es 10 mm.',
            'margin_top_mm.max' => 'El margen superior máximo es 10 mm.',
            'margin_bottom_mm.max' => 'El margen inferior máximo es 10 mm.',
            'barcode_height_mm.min' => 'El alto mínimo del barcode es 4 mm.',
            'barcode_height_mm.max' => 'El alto máximo del barcode es 25 mm.',
            'barcode_xres.min' => 'El grosor mínimo de barra es 0.20.',
            'barcode_xres.max' => 'El grosor máximo de barra es 0.80.',
        ];
    }
}
