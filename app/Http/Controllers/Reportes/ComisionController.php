<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reportes\GuardarConfiguracionComisionRequest;
use App\Models\Almacen;
use App\Models\ComisionGrupo;
use App\Models\ComisionPeriodo;
use App\Models\ComisionVendedor;
use App\Models\Linea;
use App\Models\Usuario;
use App\Services\AuditoriaService;
use App\Services\Reportes\ComisionCalculoService;
use App\Services\Reportes\ComisionConfiguracionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ComisionController extends Controller
{
    public function __construct(
        private readonly ComisionConfiguracionService $configuracion,
        private readonly ComisionCalculoService $calculo,
        private readonly AuditoriaService $auditoria,
    ) {}

    public function configuracion(Request $request)
    {
        $sucursalActivaId = $this->sucursalActiva($request);
        $sucursalesComision = $this->sucursalesPermitidas($request);
        $sucursalId = $this->sucursalConsulta($request, $sucursalesComision, $sucursalActivaId);
        $periodoTexto = $this->periodoTexto((string) $request->query('periodo', now()->format('Y-m')));
        $periodoFecha = Carbon::createFromFormat('Y-m', $periodoTexto)->startOfMonth()->toDateString();
        $periodo = ComisionPeriodo::query()
            ->with(['almacenes', 'configuracionesGrupo', 'ajustes'])
            ->where('cpe_scl_id', $sucursalId)
            ->whereDate('cpe_periodo', $periodoFecha)
            ->first();
        $grupos = ComisionGrupo::query()->with('lineas:lna_id,lna_nombre')->where('cgr_estatus', 'activo')->orderBy('cgr_nombre')->get();
        $vendedores = Usuario::query()
            ->whereHas('sucursales', fn ($query) => $query->where('tbl_sucursales_scl.scl_id', $sucursalId))
            ->where('usr_estatus', 'activo')
            ->orderBy('usr_nombre')
            ->get(['usr_id', 'usr_nombre', 'usr_usuario']);
        $perfiles = ComisionVendedor::query()->whereIn('cve_usr_id', $vendedores->pluck('usr_id'))->get()->keyBy('cve_usr_id');
        $vendedoresPeriodo = $periodo
            ? DB::table('tbl_comision_periodo_vendedores_cpv as cpv')
                ->join('tbl_comision_vendedores_cve as cve', 'cve.cve_id', '=', 'cpv.cpv_cve_id')
                ->where('cpv.cpv_cpe_id', $periodo->cpe_id)
                ->get(['cve.cve_usr_id', 'cpv.cpv_cve_id', 'cpv.cpv_cgr_id', 'cpv.cpv_numero_vendedor'])
                ->keyBy('cve_usr_id')
            : collect();
        $lineasPeriodo = $periodo
            ? DB::table('tbl_comision_periodo_lineas_cpl')->where('cpl_cpe_id', $periodo->cpe_id)->get()->groupBy('cpl_cgr_id')
            : collect();
        $ajustes = $periodo?->ajustes?->keyBy('cav_cve_id') ?? collect();

        return view('desktop.operacion.gestion_configuraciones.comisiones', [
            'activeSubmenu' => 'comisiones',
            'submenus' => $this->submenus(),
            'sucursalesComision' => $sucursalesComision,
            'sucursalSeleccionadaId' => $sucursalId,
            'sucursalActivaId' => $sucursalActivaId,
            'sucursalSoloLectura' => $sucursalId !== $sucursalActivaId,
            'periodoTexto' => $periodoTexto,
            'periodo' => $periodo,
            'grupos' => $grupos,
            'lineas' => Linea::query()->where('lna_estatus', 'activo')->orderBy('lna_nombre')->get(['lna_id', 'lna_nombre']),
            'almacenes' => Almacen::query()->where('alm_scl_id', $sucursalId)->where('alm_estatus', 'activo')->orderBy('alm_nombre')->get(['alm_id', 'alm_nombre']),
            'vendedores' => $vendedores,
            'perfiles' => $perfiles,
            'vendedoresPeriodo' => $vendedoresPeriodo,
            'lineasPeriodo' => $lineasPeriodo,
            'ajustes' => $ajustes,
            'configGrupos' => $periodo?->configuracionesGrupo?->keyBy('cpg_cgr_id') ?? collect(),
            'puedeCalcular' => $request->user()?->tienePermiso('comisiones.calcular') ?? false,
            'puedeRecalcular' => $request->user()?->tienePermiso('comisiones.recalcular') ?? false,
            'puedeCerrar' => $request->user()?->tienePermiso('comisiones.cerrar') ?? false,
        ]);
    }

    public function guardar(GuardarConfiguracionComisionRequest $request)
    {
        $datos = $request->validated();
        $sucursalId = $this->sucursalActiva($request);
        $periodoAnterior = ComisionPeriodo::query()
            ->where('cpe_scl_id', $sucursalId)
            ->whereDate('cpe_periodo', Carbon::createFromFormat('Y-m', $datos['periodo'])->startOfMonth()->toDateString())
            ->first();
        $teniaCalculo = $periodoAnterior?->resultados()->exists() ?? false;
        $antes = $this->fotografiaConfiguracion($periodoAnterior);
        $periodo = $this->configuracion->guardar(
            $datos,
            $sucursalId,
            (int) $request->user()->usr_id,
        );
        $this->auditoria->registrarAccion($request, 'comisiones.configurar', 'tbl_comision_periodos_cpe', (string) $periodo->cpe_id, [
            'periodo' => $periodo->cpe_periodo->format('Y-m'),
            'antes' => $antes,
            'despues' => $this->fotografiaConfiguracion($periodo),
        ]);

        return redirect()->route('desktop.operacion.gestion_configuraciones.comisiones.index', [
            'periodo' => $periodo->cpe_periodo->format('Y-m'),
        ])->with(
            'success',
            $teniaCalculo
                ? 'Cambios guardados. El cálculo anterior quedó pendiente de actualizar.'
                : 'Configuración guardada. El periodo está listo para calcularse.',
        );
    }

    public function calcular(Request $request)
    {
        $datos = $request->validate(['periodo' => ['required', 'date_format:Y-m']]);
        $periodo = $this->resolverPeriodo($request, $datos['periodo']);
        $esRecalculo = $periodo->cpe_estatus === 'calculado';
        $permiso = $esRecalculo ? 'comisiones.recalcular' : 'comisiones.calcular';
        abort_unless($request->user()?->tienePermiso($permiso), 403, 'No tienes permiso para '.($esRecalculo ? 'recalcular' : 'calcular').' comisiones.');
        $antes = $this->resumenPeriodoAuditoria($periodo);
        $total = $this->calculo->calcular($periodo, (int) $request->user()->usr_id);
        $periodo->refresh();
        $this->auditoria->registrarAccion($request, $esRecalculo ? 'comisiones.recalcular' : 'comisiones.calcular', 'tbl_comision_periodos_cpe', (string) $periodo->cpe_id, [
            'periodo' => $datos['periodo'],
            'vendedores' => $total,
            'antes' => $antes,
            'despues' => $this->resumenPeriodoAuditoria($periodo),
        ]);

        return redirect()->route('reportes.show', ['reporte' => 'ventas-comisiones', 'periodo' => $datos['periodo']])
            ->with('success', ($esRecalculo ? 'Comisiones recalculadas' : 'Comisiones calculadas')." para {$total} vendedores.");
    }

    public function cerrar(Request $request)
    {
        $datos = $request->validate(['periodo' => ['required', 'date_format:Y-m']]);
        $periodo = $this->resolverPeriodo($request, $datos['periodo']);
        $antes = $this->resumenPeriodoAuditoria($periodo);
        $this->calculo->cerrar($periodo, (int) $request->user()->usr_id);
        $periodo->refresh();
        $this->auditoria->registrarAccion($request, 'comisiones.cerrar', 'tbl_comision_periodos_cpe', (string) $periodo->cpe_id, [
            'periodo' => $datos['periodo'],
            'antes' => $antes,
            'despues' => $this->resumenPeriodoAuditoria($periodo),
        ]);

        return redirect()->route('reportes.show', ['reporte' => 'ventas-comisiones', 'periodo' => $datos['periodo']])
            ->with('success', 'Periodo de comisiones cerrado correctamente.');
    }

    private function resolverPeriodo(Request $request, string $periodo): ComisionPeriodo
    {
        $registro = ComisionPeriodo::query()
            ->where('cpe_scl_id', $this->sucursalActiva($request))
            ->whereDate('cpe_periodo', Carbon::createFromFormat('Y-m', $periodo)->startOfMonth()->toDateString())
            ->first();
        if (! $registro) {
            throw ValidationException::withMessages([
                'periodo' => 'Primero configura el periodo de comisiones seleccionado.',
            ]);
        }

        return $registro;
    }

    private function sucursalActiva(Request $request): int
    {
        $id = (int) $request->session()->get('sucursal_activa_id');
        abort_if($id <= 0, 422, 'No hay una sucursal activa.');

        return $id;
    }

    private function sucursalesPermitidas(Request $request)
    {
        return $request->user()->sucursales()
            ->where('tbl_sucursales_scl.scl_estatus', 'activo')
            ->where('tbl_sucursales_scl.scl_deleted', false)
            ->orderBy('tbl_sucursales_scl.scl_nombre')
            ->get(['tbl_sucursales_scl.scl_id', 'tbl_sucursales_scl.scl_nombre']);
    }

    private function sucursalConsulta(Request $request, $sucursalesPermitidas, int $sucursalActivaId): int
    {
        $sucursalSolicitada = (int) $request->query('sucursal_id');
        if ($sucursalSolicitada <= 0) {
            return $sucursalActivaId;
        }

        abort_unless(
            $sucursalesPermitidas->contains('scl_id', $sucursalSolicitada),
            403,
            'No tienes acceso a la sucursal seleccionada.',
        );

        return $sucursalSolicitada;
    }

    private function periodoTexto(string $periodo): string
    {
        try {
            return Carbon::createFromFormat('Y-m', $periodo)->format('Y-m');
        } catch (\Throwable) {
            return now()->format('Y-m');
        }
    }

    private function fotografiaConfiguracion(?ComisionPeriodo $periodo): ?array
    {
        if (! $periodo) {
            return null;
        }

        $periodo->load(['almacenes:alm_id,alm_nombre', 'configuracionesGrupo.grupo:cgr_id,cgr_nombre', 'ajustes']);
        $lineas = DB::table('tbl_comision_periodo_lineas_cpl as cpl')
            ->join('tbl_lineas_lna as lna', 'lna.lna_id', '=', 'cpl.cpl_lna_id')
            ->where('cpl.cpl_cpe_id', $periodo->cpe_id)
            ->orderBy('lna.lna_nombre')
            ->get(['cpl.cpl_cgr_id', 'cpl.cpl_lna_id', 'lna.lna_nombre'])
            ->groupBy('cpl_cgr_id');
        $ajustes = $periodo->ajustes->keyBy('cav_cve_id');
        $vendedores = DB::table('tbl_comision_periodo_vendedores_cpv as cpv')
            ->join('tbl_comision_vendedores_cve as cve', 'cve.cve_id', '=', 'cpv.cpv_cve_id')
            ->join('tbl_usuarios_usr as usr', 'usr.usr_id', '=', 'cve.cve_usr_id')
            ->join('tbl_comision_grupos_cgr as cgr', 'cgr.cgr_id', '=', 'cpv.cpv_cgr_id')
            ->where('cpv.cpv_cpe_id', $periodo->cpe_id)
            ->orderBy('cpv.cpv_numero_vendedor')
            ->get(['cpv.cpv_cve_id', 'cve.cve_usr_id', 'usr.usr_nombre', 'cpv.cpv_numero_vendedor', 'cpv.cpv_cgr_id', 'cgr.cgr_nombre'])
            ->map(function ($vendedor) use ($ajustes): array {
                $ajuste = $ajustes->get($vendedor->cpv_cve_id);

                return [
                    'usuario_id' => (int) $vendedor->cve_usr_id,
                    'nombre' => $vendedor->usr_nombre,
                    'numero' => $vendedor->cpv_numero_vendedor,
                    'grupo_id' => (int) $vendedor->cpv_cgr_id,
                    'grupo' => $vendedor->cgr_nombre,
                    'ajuste_tasa' => (float) ($ajuste?->cav_ajuste_tasa ?? 0),
                    'tasa_final' => $ajuste?->cav_tasa_final !== null ? (float) $ajuste->cav_tasa_final : null,
                    'bono' => (float) ($ajuste?->cav_bono ?? 0),
                    'motivo' => $ajuste?->cav_motivo,
                ];
            })->all();

        return [
            'estado' => $periodo->cpe_estatus,
            'factor_comisionable' => (float) $periodo->cpe_factor_comisionable,
            'tasa_general' => (float) $periodo->cpe_tasa_general,
            'cumplimiento_minimo' => (float) $periodo->cpe_cumplimiento_minimo,
            'almacenes' => $periodo->almacenes->map(fn ($almacen) => ['id' => (int) $almacen->alm_id, 'nombre' => $almacen->alm_nombre])->values()->all(),
            'grupos' => $periodo->configuracionesGrupo->map(fn ($config) => [
                'id' => (int) $config->cpg_cgr_id,
                'nombre' => $config->grupo?->cgr_nombre,
                'vendedores_promedio' => (float) $config->cpg_vendedores_promedio,
                'incremento_meta' => (float) $config->cpg_incremento_meta,
                'lineas' => collect($lineas->get($config->cpg_cgr_id, []))->map(fn ($linea) => ['id' => (int) $linea->cpl_lna_id, 'nombre' => $linea->lna_nombre])->values()->all(),
            ])->values()->all(),
            'vendedores' => $vendedores,
        ];
    }

    private function resumenPeriodoAuditoria(ComisionPeriodo $periodo): array
    {
        $periodo->load(['resultados', 'configuracionesGrupo']);

        return [
            'estado' => $periodo->cpe_estatus,
            'factor_comisionable' => (float) $periodo->cpe_factor_comisionable,
            'tasa_general' => (float) $periodo->cpe_tasa_general,
            'cumplimiento_minimo' => (float) $periodo->cpe_cumplimiento_minimo,
            'vendedores' => $periodo->resultados->count(),
            'ventas_totales' => round((float) $periodo->resultados->sum('crs_ventas_totales'), 2),
            'comisiones' => round((float) $periodo->resultados->sum('crs_comision'), 2),
            'bonos' => round((float) $periodo->resultados->sum('crs_bono'), 2),
            'total_pagar' => round((float) $periodo->resultados->sum('crs_total_pagar'), 2),
            'grupos' => $periodo->configuracionesGrupo->map(fn ($grupo) => [
                'grupo_id' => (int) $grupo->cpg_cgr_id,
                'ventas_grupo' => (float) $grupo->cpg_ventas_grupo,
                'ventas_sin_atencion' => (float) $grupo->cpg_ventas_sin_atencion,
                'meta_individual' => (float) $grupo->cpg_meta_individual,
            ])->values()->all(),
            'calculado_at' => $periodo->cpe_calculado_at?->toIso8601String(),
            'cerrado_at' => $periodo->cpe_cerrado_at?->toIso8601String(),
        ];
    }

    private function submenus(): array
    {
        return [
            ['key' => 'sucursales', 'label' => 'Sucursales', 'route' => route('desktop.operacion.gestion_configuraciones.sucursales.index')],
            ['key' => 'almacenes', 'label' => 'Almacenes', 'route' => route('desktop.operacion.gestion_configuraciones.almacenes.index')],
            ['key' => 'tipos_almacen', 'label' => 'Tipos de almacén', 'route' => route('desktop.operacion.gestion_configuraciones.tipos_almacen.index')],
            ['key' => 'cajas', 'label' => 'Cajas', 'route' => route('desktop.operacion.gestion_configuraciones.cajas.index')],
            ['key' => 'clientes', 'label' => 'Clientes', 'route' => route('desktop.operacion.gestion_configuraciones.clientes.index')],
            ['key' => 'comisiones', 'label' => 'Comisiones', 'route' => route('desktop.operacion.gestion_configuraciones.comisiones.index')],
            ['key' => 'ticket', 'label' => 'Personalizar ticket', 'route' => route('desktop.operacion.gestion_configuraciones.ticket.index')],
            ['key' => 'impresoras', 'label' => 'Impresoras', 'route' => route('desktop.operacion.gestion_configuraciones.impresoras.index')],
        ];
    }
}
