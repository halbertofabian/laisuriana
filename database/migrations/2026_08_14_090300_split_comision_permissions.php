<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $ahora = now();
        DB::table('tbl_permisos_prm')
            ->where('prm_clave', 'comisiones.calcular')
            ->update([
                'prm_descripcion' => 'Calcular comisiones por primera vez',
                'prm_updated_at' => $ahora,
            ]);

        foreach ([
            ['comisiones.recalcular', 'Recalcular periodos de comisión no cerrados'],
            ['comisiones.exportar', 'Exportar el reporte de comisiones'],
        ] as [$clave, $descripcion]) {
            DB::table('tbl_permisos_prm')->updateOrInsert(
                ['prm_clave' => $clave],
                [
                    'prm_descripcion' => $descripcion,
                    'prm_modulo' => 'reportes',
                    'prm_estatus' => 'activo',
                    'prm_deleted' => false,
                    'prm_deleted_at' => null,
                    'prm_updated_at' => $ahora,
                ],
            );
        }

        $ids = DB::table('tbl_permisos_prm')
            ->whereIn('prm_clave', ['comisiones.ver', 'comisiones.calcular', 'comisiones.recalcular', 'comisiones.exportar', 'reportes.exportar'])
            ->pluck('prm_id', 'prm_clave');

        $rolesCalculo = DB::table('tbl_rol_permisos_rpm')
            ->where('rpm_prm_id', $ids['comisiones.calcular'])
            ->where('rpm_estatus', 'activo')
            ->where('rpm_deleted', false)
            ->pluck('rpm_rol_id');
        foreach ($rolesCalculo as $rolId) {
            $this->asignar((int) $rolId, (int) $ids['comisiones.recalcular'], $ahora);
        }

        $rolesExportacion = DB::table('tbl_rol_permisos_rpm')
            ->whereIn('rpm_prm_id', [$ids['comisiones.ver'], $ids['reportes.exportar']])
            ->where('rpm_estatus', 'activo')
            ->where('rpm_deleted', false)
            ->groupBy('rpm_rol_id')
            ->havingRaw('COUNT(DISTINCT rpm_prm_id) = 2')
            ->pluck('rpm_rol_id');
        foreach ($rolesExportacion as $rolId) {
            $this->asignar((int) $rolId, (int) $ids['comisiones.exportar'], $ahora);
        }
    }

    public function down(): void
    {
        DB::table('tbl_permisos_prm')
            ->where('prm_clave', 'comisiones.calcular')
            ->update([
                'prm_descripcion' => 'Calcular y recalcular comisiones',
                'prm_updated_at' => now(),
            ]);

        $ids = DB::table('tbl_permisos_prm')
            ->whereIn('prm_clave', ['comisiones.recalcular', 'comisiones.exportar'])
            ->pluck('prm_id');
        DB::table('tbl_rol_permisos_rpm')->whereIn('rpm_prm_id', $ids)->delete();
        DB::table('tbl_permisos_prm')->whereIn('prm_id', $ids)->delete();
    }

    private function asignar(int $rolId, int $permisoId, $ahora): void
    {
        DB::table('tbl_rol_permisos_rpm')->updateOrInsert(
            ['rpm_rol_id' => $rolId, 'rpm_prm_id' => $permisoId],
            [
                'rpm_estatus' => 'activo',
                'rpm_deleted' => false,
                'rpm_deleted_at' => null,
                'rpm_updated_at' => $ahora,
            ],
        );
    }
};
