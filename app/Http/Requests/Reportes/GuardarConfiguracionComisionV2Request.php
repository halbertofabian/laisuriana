<?php

namespace App\Http\Requests\Reportes;

use App\Models\ComisionV2Departamento;
use Illuminate\Foundation\Http\FormRequest;

class GuardarConfiguracionComisionV2Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $normalizar = fn ($filas) => collect(is_array($filas) ? $filas : [])->map(function ($fila) {
            $fila = is_array($fila) ? $fila : [];
            $fila['habilitado'] = isset($fila['habilitado']) && (string) $fila['habilitado'] === '1';
            foreach (['meta', 'meta_comun'] as $campo) {
                if (array_key_exists($campo, $fila) && $fila[$campo] === '') {
                    $fila[$campo] = null;
                }
            }
            return $fila;
        })->all();

        $this->merge([
            'departamentos' => $normalizar($this->input('departamentos', [])),
            'vendedores' => $normalizar($this->input('vendedores', [])),
        ]);
    }

    public function rules(): array
    {
        return [
            'periodo' => ['required', 'date_format:Y-m'],
            'almacen_ids' => ['required', 'array', 'min:1'],
            'almacen_ids.*' => ['integer', 'distinct', 'exists:tbl_almacenes_alm,alm_id'],
            'motivo_cambio' => ['nullable', 'string', 'max:1000'],
            'departamentos' => ['required', 'array'],
            'departamentos.*.habilitado' => ['boolean'],
            'departamentos.*.linea_ids' => ['nullable', 'array'],
            'departamentos.*.linea_ids.*' => ['integer', 'distinct', 'exists:tbl_lineas_lna,lna_id'],
            'departamentos.*.incremento_meta' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'departamentos.*.meta_comun' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'vendedores' => ['nullable', 'array'],
            'vendedores.*.habilitado' => ['boolean'],
            'vendedores.*.numero' => ['nullable', 'string', 'max:40', 'distinct'],
            'vendedores.*.departamento_id' => ['nullable', 'integer', 'exists:tbl_comision_v2_departamentos_cmd,cmd_id'],
            'vendedores.*.meta' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'vendedores.*.tasa' => ['nullable', 'numeric', 'in:0,0.1,0.2,0.3,0.4,0.5,0.6,0.7,0.8,0.9,1'],
            'vendedores.*.motivo' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $departamentos = ComisionV2Departamento::query()->where('cmd_estatus', 'activo')->get()->keyBy('cmd_id');
            $habilitados = [];
            $lineasUsadas = [];
            foreach ((array) $this->input('departamentos', []) as $departamentoId => $config) {
                if (! ($config['habilitado'] ?? false)) {
                    continue;
                }
                if (! $departamentos->has((int) $departamentoId)) {
                    $validator->errors()->add("departamentos.$departamentoId", 'El departamento no está disponible.');
                    continue;
                }
                $habilitados[(int) $departamentoId] = true;
                if (empty($config['linea_ids'])) {
                    $validator->errors()->add("departamentos.$departamentoId.linea_ids", 'Selecciona al menos una línea.');
                }
                foreach ((array) ($config['linea_ids'] ?? []) as $lineaId) {
                    if (isset($lineasUsadas[(int) $lineaId])) {
                        $validator->errors()->add("departamentos.$departamentoId.linea_ids", 'Una línea solo puede pertenecer a un departamento durante el periodo.');
                    }
                    $lineasUsadas[(int) $lineaId] = true;
                }
            }
            if ($habilitados === []) {
                $validator->errors()->add('departamentos', 'Activa al menos un departamento.');
            }

            foreach ((array) $this->input('vendedores', []) as $usuarioId => $fila) {
                if (! ($fila['habilitado'] ?? false)) {
                    continue;
                }
                if (trim((string) ($fila['numero'] ?? '')) === '') {
                    $validator->errors()->add("vendedores.$usuarioId.numero", 'Captura el número del vendedor.');
                }
                $departamentoId = (int) ($fila['departamento_id'] ?? 0);
                if (! isset($habilitados[$departamentoId])) {
                    $validator->errors()->add("vendedores.$usuarioId.departamento_id", 'Selecciona un departamento activo.');
                }
                $tasa = (float) ($fila['tasa'] ?? 0.9);
                $meta = $fila['meta'] ?? null;
                $metaComun = $this->input("departamentos.$departamentoId.meta_comun");
                $requiereMotivo = abs($tasa - 0.9) > 0.00001
                    || ($meta !== null && $metaComun !== null && abs((float) $meta - (float) $metaComun) > 0.009);
                if ($requiereMotivo && trim((string) ($fila['motivo'] ?? '')) === '') {
                    $validator->errors()->add("vendedores.$usuarioId.motivo", 'Explica el ajuste individual de meta o tasa.');
                }
            }
        });
    }
}
