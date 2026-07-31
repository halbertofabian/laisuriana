<?php

namespace App\Http\Requests\Operacion\Etiquetas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEtiquetaFormatoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $formatoId = $this->route('formato');

        return [
            'etf_nombre' => [
                'required',
                'string',
                'max:120',
                Rule::unique('tbl_etiqueta_formatos_etf', 'etf_nombre')
                    ->ignore($formatoId, 'etf_id')
                    ->where('etf_deleted', false),
            ],
            'etf_descripcion' => ['nullable', 'string'],
            'etf_ancho_mm' => ['required', 'numeric', 'min:10', 'max:500'],
            'etf_alto_mm' => ['required', 'numeric', 'min:10', 'max:500'],
            'etf_orientacion' => ['required', Rule::in(['auto', 'vertical', 'horizontal'])],
            'etf_margen_izq_mm' => ['nullable', 'numeric', 'min:0', 'max:50'],
            'etf_margen_der_mm' => ['nullable', 'numeric', 'min:0', 'max:50'],
            'etf_margen_sup_mm' => ['nullable', 'numeric', 'min:0', 'max:50'],
            'etf_margen_inf_mm' => ['nullable', 'numeric', 'min:0', 'max:50'],
            'etf_tipo_salida' => ['required', Rule::in(['termica', 'hoja'])],
            'etf_columnas' => ['required_if:etf_tipo_salida,hoja', 'nullable', 'integer', 'min:1', 'max:20'],
            'etf_filas' => ['required_if:etf_tipo_salida,hoja', 'nullable', 'integer', 'min:1', 'max:20'],
            'etf_separacion_h_mm' => ['nullable', 'numeric', 'min:0', 'max:50'],
            'etf_separacion_v_mm' => ['nullable', 'numeric', 'min:0', 'max:50'],
            'etf_compatibilidad_impresora' => ['nullable', 'string', 'max:120'],
            'etf_estatus' => ['required', Rule::in(['activo', 'inactivo'])],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $ancho = (float) $this->input('etf_ancho_mm');
                $alto = (float) $this->input('etf_alto_mm');
                if ($this->input('etf_orientacion') === 'horizontal' && $alto > $ancho) {
                    [$ancho, $alto] = [$alto, $ancho];
                } elseif ($this->input('etf_orientacion') === 'vertical' && $ancho > $alto) {
                    [$ancho, $alto] = [$alto, $ancho];
                }

                $anchoUtil = $ancho
                    - (float) $this->input('etf_margen_izq_mm', 0)
                    - (float) $this->input('etf_margen_der_mm', 0);
                $altoUtil = $alto
                    - (float) $this->input('etf_margen_sup_mm', 0)
                    - (float) $this->input('etf_margen_inf_mm', 0);

                if ($anchoUtil <= 0) {
                    $validator->errors()->add('etf_margen_der_mm', 'Los márgenes laterales deben dejar espacio útil dentro de la etiqueta.');
                }
                if ($altoUtil <= 0) {
                    $validator->errors()->add('etf_margen_inf_mm', 'Los márgenes superior e inferior deben dejar espacio útil dentro de la etiqueta.');
                }
            },
        ];
    }
}
