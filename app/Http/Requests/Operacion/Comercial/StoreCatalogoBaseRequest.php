<?php

namespace App\Http\Requests\Operacion\Comercial;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StoreCatalogoBaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tipo = (string) $this->route('tipo');
        $config = $this->catalogoConfig($tipo);

        // Para categorías el unique de nombre se valida en withValidator (compuesto con lna_id)
        $nombreRule = $tipo === 'categorias'
            ? ['required', 'string', 'max:120']
            : ['required', 'string', 'max:120', Rule::unique($config['table'], $config['nombre'])->where(fn ($query) => $query->where($config['deleted'], false))];

        $rules = [
            'nombre' => $nombreRule,
            'clave' => [
                'nullable',
                'string',
                'max:40',
                Rule::unique($config['table'], $config['clave'])->where(fn ($query) => $query->where($config['deleted'], false)),
            ],
            'estatus' => ['required', Rule::in(['activo', 'inactivo'])],
        ];

        if ($tipo === 'categorias') {
            $rules['lna_id'] = ['required', 'integer', Rule::exists('tbl_lineas_lna', 'lna_id')];
        }

        if ($tipo === 'unidades') {
            $rules['codigo'] = [
                'nullable',
                'string',
                'max:20',
                Rule::unique($config['table'], 'umd_codigo')->where(fn ($query) => $query->where('umd_deleted', false)),
            ];
            $rules['tipo_cantidad'] = ['required', Rule::in(['entero', 'decimal'])];
            $rules['es_predeterminada'] = ['nullable', 'boolean'];
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $tipo = (string) $this->route('tipo');

        if ($tipo !== 'categorias') {
            return;
        }

        $validator->after(function ($validator): void {
            if ($validator->errors()->has('lna_id') || $validator->errors()->has('nombre')) {
                return;
            }

            $existe = DB::table('tbl_categorias_ctg')
                ->where('ctg_nombre', $this->input('nombre'))
                ->where('ctg_lna_id', $this->input('lna_id'))
                ->where('ctg_deleted', false)
                ->exists();

            if ($existe) {
                $validator->errors()->add('nombre', 'El nombre del concepto ya existe para la línea seleccionada.');
            }
        });
    }

    public function messages(): array
    {
        $tipo = (string) $this->route('tipo');
        $nombreUnique = $tipo === 'lineas'
            ? 'El nombre de la línea ya existe.'
            : ($tipo === 'unidades'
                ? 'El nombre de la unidad ya existe.'
                : ($tipo === 'descripciones'
                    ? 'El nombre de la descripción ya existe.'
                : ($tipo === 'motivos'
                    ? 'El nombre del motivo ya existe.'
                    : 'Ya existe un registro con ese nombre.')));

        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.unique' => $nombreUnique,
            'lna_id.required' => 'Debes seleccionar una línea para la categoría.',
            'lna_id.exists' => 'La línea seleccionada no existe.',
            'clave.unique' => 'Ya existe un registro con esa clave.',
            'codigo.unique' => 'Ya existe una unidad con ese código.',
            'tipo_cantidad.required' => 'El tipo de cantidad es obligatorio.',
            'tipo_cantidad.in' => 'El tipo de cantidad debe ser "entero" o "decimal".',
            'estatus.required' => 'El estatus es obligatorio.',
            'estatus.in' => 'El estatus enviado no es válido.',
        ];
    }

    private function catalogoConfig(string $tipo): array
    {
        return match ($tipo) {
            'marcas' => ['table' => 'tbl_marcas_mrc', 'nombre' => 'mrc_nombre', 'clave' => 'mrc_clave', 'deleted' => 'mrc_deleted'],
            'lineas' => ['table' => 'tbl_lineas_lna', 'nombre' => 'lna_nombre', 'clave' => 'lna_clave', 'deleted' => 'lna_deleted'],
            'categorias' => ['table' => 'tbl_categorias_ctg', 'nombre' => 'ctg_nombre', 'clave' => 'ctg_clave', 'deleted' => 'ctg_deleted'],
            'unidades' => ['table' => 'tbl_unidades_medida_umd', 'nombre' => 'umd_nombre', 'clave' => 'umd_clave', 'deleted' => 'umd_deleted'],
            'descripciones' => ['table' => 'tbl_descripciones_dsc', 'nombre' => 'dsc_nombre', 'clave' => 'dsc_clave', 'deleted' => 'dsc_deleted'],
            'conceptos' => ['table' => 'tbl_conceptos_cpt', 'nombre' => 'cpt_nombre', 'clave' => 'cpt_clave', 'deleted' => 'cpt_deleted'],
            'motivos' => ['table' => 'tbl_motivos_mtv', 'nombre' => 'mtv_nombre', 'clave' => 'mtv_clave', 'deleted' => 'mtv_deleted'],
            default => throw new \InvalidArgumentException('Tipo de catálogo no soportado.'),
        };
    }
}
