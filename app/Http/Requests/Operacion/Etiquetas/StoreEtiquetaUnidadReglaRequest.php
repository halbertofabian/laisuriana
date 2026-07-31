<?php

namespace App\Http\Requests\Operacion\Etiquetas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEtiquetaUnidadReglaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'eur_umd_id' => ['required', 'integer', 'exists:tbl_unidades_medida_umd,umd_id'],
            'eur_regla' => ['required', Rule::in(['por_unidad_recibida', 'por_detalle_recepcion'])],
        ];
    }
}
