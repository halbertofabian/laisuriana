<?php

namespace App\Http\Requests\Reportes;

use App\Models\ComisionGrupo;
use Illuminate\Foundation\Http\FormRequest;

class GuardarConfiguracionComisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $vendedores = collect($this->input('vendedores', []))->map(function ($fila) {
            $fila = is_array($fila) ? $fila : [];
            $fila['habilitado'] = isset($fila['habilitado']) && (string) $fila['habilitado'] === '1';
            $fila['tasa_final'] = ($fila['tasa_final'] ?? '') === '' ? null : $fila['tasa_final'];

            return $fila;
        })->all();

        $this->merge(['vendedores' => $vendedores]);
    }

    public function rules(): array
    {
        return [
            'periodo' => ['required', 'date_format:Y-m'],
            'factor_comisionable' => ['required', 'numeric', 'min:0', 'max:100'],
            'tasa_general' => ['required', 'numeric', 'min:0', 'max:100'],
            'cumplimiento_minimo' => ['required', 'numeric', 'min:0', 'max:1000'],
            'almacen_ids' => ['required', 'array', 'min:1'],
            'almacen_ids.*' => ['integer', 'distinct', 'exists:tbl_almacenes_alm,alm_id'],
            'grupos' => ['required', 'array'],
            'grupos.*.linea_ids' => ['nullable', 'array'],
            'grupos.*.linea_ids.*' => ['integer', 'distinct', 'exists:tbl_lineas_lna,lna_id'],
            'grupos.*.vendedores_promedio' => ['required', 'numeric', 'gt:0', 'max:9999'],
            'grupos.*.incremento_meta' => ['required', 'numeric', 'min:0', 'max:100'],
            'vendedores' => ['nullable', 'array'],
            'vendedores.*.habilitado' => ['boolean'],
            'vendedores.*.numero' => ['nullable', 'string', 'max:40', 'distinct'],
            'vendedores.*.grupo_id' => ['nullable', 'integer', 'exists:tbl_comision_grupos_cgr,cgr_id'],
            'vendedores.*.ajuste_tasa' => ['nullable', 'numeric', 'min:-100', 'max:100'],
            'vendedores.*.tasa_final' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'vendedores.*.bono' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'vendedores.*.motivo' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $grupos = ComisionGrupo::query()->get()->keyBy('cgr_id');
            $lineasUsadas = [];

            foreach ((array) $this->input('grupos', []) as $grupoId => $config) {
                $grupo = $grupos->get((int) $grupoId);
                if (! $grupo) {
                    $validator->errors()->add("grupos.$grupoId", 'El grupo de comisión no existe.');

                    continue;
                }

                $incremento = (float) ($config['incremento_meta'] ?? 0);
                if ($incremento < (float) $grupo->cgr_incremento_minimo || $incremento > (float) $grupo->cgr_incremento_maximo) {
                    $validator->errors()->add(
                        "grupos.$grupoId.incremento_meta",
                        "El incremento de {$grupo->cgr_nombre} debe estar entre {$grupo->cgr_incremento_minimo}% y {$grupo->cgr_incremento_maximo}%."
                    );
                }

                foreach ((array) ($config['linea_ids'] ?? []) as $lineaId) {
                    if (isset($lineasUsadas[(int) $lineaId])) {
                        $validator->errors()->add("grupos.$grupoId.linea_ids", 'Una línea solo puede pertenecer a un grupo de comisión.');
                    }
                    $lineasUsadas[(int) $lineaId] = true;
                }
            }

            foreach ((array) $this->input('vendedores', []) as $usuarioId => $fila) {
                if (! ($fila['habilitado'] ?? false)) {
                    continue;
                }
                if (trim((string) ($fila['numero'] ?? '')) === '') {
                    $validator->errors()->add("vendedores.$usuarioId.numero", 'Captura el número del vendedor.');
                }
                if (empty($fila['grupo_id'])) {
                    $validator->errors()->add("vendedores.$usuarioId.grupo_id", 'Selecciona el grupo del vendedor.');
                }
            }
        });
    }
}
