<?php

namespace App\Http\Requests\Reportes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarHistoricoVentasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (int) $this->session()->get('sucursal_activa_id') > 0
            && ($this->user()?->tienePermiso('comisiones.configurar') ?? false);
    }

    public function rules(): array
    {
        $sucursalId = (int) $this->session()->get('sucursal_activa_id');

        return [
            'id' => ['nullable', 'integer', 'min:1'],
            'version' => ['required_with:id', 'nullable', 'integer', 'min:1'],
            'periodo' => ['required', 'date_format:Y-m', 'before:'.now()->startOfMonth()->format('Y-m')],
            'almacen_id' => ['required', 'integer', Rule::exists('tbl_almacenes_alm', 'alm_id')->where(fn ($query) => $query->where('alm_scl_id', $sucursalId)->where('alm_deleted', false))],
            'linea_id' => ['required', 'integer', Rule::exists('tbl_lineas_lna', 'lna_id')->where(fn ($query) => $query->where('lna_deleted', false))],
            'ventas_netas' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:999999999999.99'],
            'autoservicio' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'lte:ventas_netas'],
            'referencia' => ['required', 'string', 'max:250'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'motivo' => ['required_with:id', 'nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'periodo.before' => 'Selecciona un mes anterior al actual para capturar el total mensual.',
            'autoservicio.lte' => 'El autoservicio no puede superar las ventas netas totales.',
            'motivo.required_with' => 'Explica el motivo de la corrección.',
            'almacen_id.exists' => 'Selecciona un almacén de la sucursal actual.',
        ];
    }

    public function attributes(): array
    {
        return ['periodo' => 'mes de las ventas', 'linea_id' => 'línea', 'ventas_netas' => 'ventas netas', 'referencia' => 'referencia del reporte'];
    }
}
