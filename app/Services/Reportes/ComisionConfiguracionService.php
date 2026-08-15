<?php

namespace App\Services\Reportes;

use App\Models\ComisionAjusteVendedor;
use App\Models\ComisionGrupo;
use App\Models\ComisionPeriodo;
use App\Models\ComisionPeriodoGrupo;
use App\Models\ComisionVendedor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ComisionConfiguracionService
{
    public function guardar(array $datos, int $sucursalId, int $usuarioId): ComisionPeriodo
    {
        return DB::transaction(function () use ($datos, $sucursalId, $usuarioId): ComisionPeriodo {
            $periodoFecha = Carbon::createFromFormat('Y-m', $datos['periodo'])->startOfMonth()->toDateString();
            $periodo = ComisionPeriodo::query()
                ->where('cpe_scl_id', $sucursalId)
                ->whereDate('cpe_periodo', $periodoFecha)
                ->lockForUpdate()
                ->first();

            if ($periodo?->cpe_estatus === 'cerrado') {
                throw ValidationException::withMessages(['periodo' => 'El periodo está cerrado y ya no admite cambios.']);
            }

            $almacenesValidos = DB::table('tbl_almacenes_alm')
                ->whereIn('alm_id', $datos['almacen_ids'])
                ->where('alm_scl_id', $sucursalId)
                ->where('alm_estatus', 'activo')
                ->where('alm_deleted', false)
                ->pluck('alm_id')
                ->map(fn ($id) => (int) $id)
                ->all();
            if (count($almacenesValidos) !== count(array_unique(array_map('intval', $datos['almacen_ids'])))) {
                throw ValidationException::withMessages(['almacen_ids' => 'Todos los almacenes deben estar activos y pertenecer a la sucursal actual.']);
            }

            $periodo ??= new ComisionPeriodo([
                'cpe_scl_id' => $sucursalId,
                'cpe_periodo' => $periodoFecha,
                'cpe_created_by_usr_id' => $usuarioId,
            ]);
            $periodo->fill([
                'cpe_factor_comisionable' => $datos['factor_comisionable'],
                'cpe_tasa_general' => $datos['tasa_general'],
                'cpe_cumplimiento_minimo' => $datos['cumplimiento_minimo'],
                'cpe_estatus' => 'borrador',
                'cpe_calculado_at' => null,
                'cpe_calculado_by_usr_id' => null,
                'cpe_updated_by_usr_id' => $usuarioId,
            ]);
            $periodo->save();

            $periodo->almacenes()->sync($almacenesValidos);

            $grupos = ComisionGrupo::query()->where('cgr_estatus', 'activo')->get();
            DB::table('tbl_comision_grupo_lineas_cgl')->delete();
            DB::table('tbl_comision_periodo_lineas_cpl')->where('cpl_cpe_id', $periodo->cpe_id)->delete();
            foreach ($grupos as $grupo) {
                $config = $datos['grupos'][$grupo->cgr_id] ?? [];
                $lineaIds = collect($config['linea_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values()->all();
                $grupo->lineas()->sync($lineaIds);
                foreach ($lineaIds as $lineaId) {
                    DB::table('tbl_comision_periodo_lineas_cpl')->insert([
                        'cpl_cpe_id' => $periodo->cpe_id,
                        'cpl_cgr_id' => $grupo->cgr_id,
                        'cpl_lna_id' => $lineaId,
                        'cpl_created_at' => now(),
                        'cpl_updated_at' => now(),
                    ]);
                }

                ComisionPeriodoGrupo::query()->updateOrCreate(
                    ['cpg_cpe_id' => $periodo->cpe_id, 'cpg_cgr_id' => $grupo->cgr_id],
                    [
                        'cpg_vendedores_promedio' => $config['vendedores_promedio'],
                        'cpg_incremento_meta' => $config['incremento_meta'],
                        'cpg_ventas_grupo' => 0,
                        'cpg_ventas_sin_atencion' => 0,
                        'cpg_base_meta' => 0,
                        'cpg_meta_individual' => 0,
                    ]
                );
            }

            $usuariosValidos = DB::table('tbl_usuario_sucursales_usc')
                ->where('usc_scl_id', $sucursalId)
                ->where('usc_estatus', 'activo')
                ->where('usc_deleted', false)
                ->whereNull('usc_deleted_at')
                ->pluck('usc_usr_id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $perfiles = [];
            DB::table('tbl_comision_periodo_vendedores_cpv')->where('cpv_cpe_id', $periodo->cpe_id)->delete();
            foreach ((array) ($datos['vendedores'] ?? []) as $usuarioIdFila => $fila) {
                if (! in_array((int) $usuarioIdFila, $usuariosValidos, true)) {
                    throw ValidationException::withMessages(['vendedores' => 'Todos los vendedores deben pertenecer a la sucursal actual.']);
                }
                $perfil = ComisionVendedor::query()->firstWhere('cve_usr_id', (int) $usuarioIdFila);
                if (! ($fila['habilitado'] ?? false)) {
                    if ($perfil) {
                        $perfil->update(['cve_estatus' => 'inactivo', 'cve_updated_by_usr_id' => $usuarioId]);
                    }

                    continue;
                }

                $perfil = ComisionVendedor::query()->updateOrCreate(
                    ['cve_usr_id' => (int) $usuarioIdFila],
                    [
                        'cve_cgr_id' => (int) $fila['grupo_id'],
                        'cve_numero' => trim((string) $fila['numero']),
                        'cve_estatus' => 'activo',
                        'cve_created_by_usr_id' => $perfil?->cve_created_by_usr_id ?: $usuarioId,
                        'cve_updated_by_usr_id' => $usuarioId,
                    ]
                );
                $perfiles[$perfil->cve_id] = $fila;
                DB::table('tbl_comision_periodo_vendedores_cpv')->insert([
                    'cpv_cpe_id' => $periodo->cpe_id,
                    'cpv_cve_id' => $perfil->cve_id,
                    'cpv_cgr_id' => (int) $fila['grupo_id'],
                    'cpv_numero_vendedor' => trim((string) $fila['numero']),
                    'cpv_created_at' => now(),
                    'cpv_updated_at' => now(),
                ]);
            }

            ComisionAjusteVendedor::query()->where('cav_cpe_id', $periodo->cpe_id)->delete();
            foreach ($perfiles as $perfilId => $fila) {
                $ajuste = (float) ($fila['ajuste_tasa'] ?? 0);
                $tasaFinal = $fila['tasa_final'] ?? null;
                $bono = (float) ($fila['bono'] ?? 0);
                $motivo = trim((string) ($fila['motivo'] ?? '')) ?: null;
                if ($ajuste == 0.0 && $tasaFinal === null && $bono == 0.0 && $motivo === null) {
                    continue;
                }
                ComisionAjusteVendedor::query()->updateOrCreate(
                    ['cav_cpe_id' => $periodo->cpe_id, 'cav_cve_id' => $perfilId],
                    [
                        'cav_ajuste_tasa' => $ajuste,
                        'cav_tasa_final' => $tasaFinal,
                        'cav_bono' => $bono,
                        'cav_motivo' => $motivo,
                    ]
                );
            }

            $periodo->resultados()->delete();

            return $periodo->fresh(['almacenes', 'configuracionesGrupo', 'ajustes']);
        });
    }
}
