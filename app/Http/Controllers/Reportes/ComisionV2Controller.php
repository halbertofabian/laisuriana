<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reportes\GuardarConfiguracionComisionV2Request;
use App\Models\Almacen;
use App\Models\ComisionV2Departamento;
use App\Models\ComisionV2Periodo;
use App\Models\Linea;
use App\Models\Usuario;
use App\Services\AuditoriaService;
use App\Services\Reportes\ComisionV2Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ComisionV2Controller extends Controller
{
    public function __construct(
        private readonly ComisionV2Service $comisiones,
        private readonly AuditoriaService $auditoria,
    ) {}

    public function configuracion(Request $request)
    {
        $sucursalId = $this->sucursalActiva($request);
        $periodoTexto = $this->periodoTexto((string) $request->query('periodo', now()->format('Y-m')));
        $periodo = ComisionV2Periodo::query()
            ->with(['almacenes', 'departamentos.participantes', 'participantes'])
            ->where('cmp_scl_id', $sucursalId)
            ->whereDate('cmp_periodo', Carbon::createFromFormat('Y-m', $periodoTexto)->startOfMonth()->toDateString())
            ->first();
        $departamentos = ComisionV2Departamento::query()
            ->with('lineas:lna_id,lna_nombre')
            ->where('cmd_estatus', 'activo')
            ->orderBy('cmd_nombre')->get();
        $usuarios = Usuario::query()
            ->whereHas('sucursales', fn ($query) => $query->where('tbl_sucursales_scl.scl_id', $sucursalId))
            ->where('usr_estatus', 'activo')->where('usr_deleted', false)
            ->orderBy('usr_nombre')->get(['usr_id', 'usr_nombre', 'usr_usuario']);

        $lineasPeriodo = $periodo
            ? DB::table('tbl_comision_v2_periodo_lineas_cml as cml')
                ->join('tbl_comision_v2_periodo_departamentos_cpd as cpd', 'cpd.cpd_id', '=', 'cml.cml_cpd_id')
                ->where('cml.cml_cmp_id', $periodo->cmp_id)
                ->get(['cpd.cpd_cmd_id', 'cml.cml_lna_id'])->groupBy('cpd_cmd_id')
            : collect();
        $configDepartamentos = $periodo?->departamentos?->keyBy('cpd_cmd_id') ?? collect();
        $participantes = $periodo?->participantes?->keyBy('cpt_usr_id') ?? collect();

        return view('desktop.operacion.gestion_configuraciones.comisiones', [
            'activeSubmenu' => 'comisiones',
            'submenus' => $this->submenus(),
            'periodoTexto' => $periodoTexto,
            'periodo' => $periodo,
            'almacenes' => Almacen::query()->where('alm_scl_id', $sucursalId)->where('alm_estatus', 'activo')->where('alm_deleted', false)->orderBy('alm_nombre')->get(['alm_id', 'alm_nombre']),
            'almacenesSeleccionados' => $periodo?->almacenes?->pluck('alm_id')->all() ?? [],
            'departamentos' => $departamentos,
            'departamentosCatalogo' => ComisionV2Departamento::query()->orderBy('cmd_nombre')->get(),
            'lineas' => Linea::query()->where('lna_estatus', 'activo')->where('lna_deleted', false)->orderBy('lna_nombre')->get(['lna_id', 'lna_nombre']),
            'usuarios' => $usuarios,
            'lineasPeriodo' => $lineasPeriodo,
            'configDepartamentos' => $configDepartamentos,
            'participantes' => $participantes,
            'estimaciones' => $periodo && ($request->user()?->tienePermiso('comisiones.estimar') ?? false)
                ? $this->comisiones->estimacionAdministrativa($periodo)
                : collect(),
            'puedeAprobar' => $request->user()?->tienePermiso('comisiones.aprobar') ?? false,
            'puedeCerrar' => $request->user()?->tienePermiso('comisiones.cerrar') ?? false,
            'legacyCount' => DB::table('tbl_comision_periodos_cpe')->count(),
        ]);
    }

    public function guardar(GuardarConfiguracionComisionV2Request $request)
    {
        $datos = $request->validated();
        $sucursalId = $this->sucursalActiva($request);
        $fecha = Carbon::createFromFormat('Y-m', $datos['periodo'])->startOfMonth();
        $antes = $this->snapshotPeriodo(ComisionV2Periodo::query()
            ->where('cmp_scl_id', $sucursalId)
            ->whereDate('cmp_periodo', $fecha->toDateString())->first());
        $periodo = $this->comisiones->guardar($datos, $sucursalId, (int) $request->user()->usr_id);
        $this->auditoria->registrarAccion($request, 'comisiones.v2.configurar', 'tbl_comision_v2_periodos_cmp', (string) $periodo->cmp_id, [
            'periodo' => $periodo->cmp_periodo->format('Y-m'),
            'motivo' => $datos['motivo_cambio'] ?? null,
            'antes' => $antes,
            'despues' => $this->snapshotPeriodo($periodo),
        ]);
        return redirect()->route('desktop.operacion.gestion_configuraciones.comisiones.index', ['periodo' => $periodo->cmp_periodo->format('Y-m')])
            ->with('success', $periodo->cmp_estatus === 'aprobado' ? 'Cambios guardados y estimados actualizados.' : 'Borrador guardado. Revisa el resumen antes de aprobarlo.');
    }

    public function aprobar(Request $request)
    {
        $datos = $request->validate(['periodo' => ['required', 'date_format:Y-m']]);
        $periodo = $this->resolverPeriodo($request, $datos['periodo']);
        $antes = $this->snapshotPeriodo($periodo);
        $periodo = $this->comisiones->aprobar($periodo, (int) $request->user()->usr_id);
        $this->auditoria->registrarAccion($request, 'comisiones.v2.aprobar', 'tbl_comision_v2_periodos_cmp', (string) $periodo->cmp_id, [
            'periodo' => $datos['periodo'],
            'antes' => $antes,
            'despues' => $this->snapshotPeriodo($periodo),
        ]);
        return back()->with('success', 'Metas y equipo aprobados. El avance ya está disponible para los vendedores.');
    }

    public function cerrar(Request $request)
    {
        $datos = $request->validate(['periodo' => ['required', 'date_format:Y-m']]);
        $periodo = $this->resolverPeriodo($request, $datos['periodo']);
        $antes = $this->snapshotPeriodo($periodo);
        $total = $this->comisiones->cerrar($periodo, (int) $request->user()->usr_id);
        $periodo = $periodo->fresh();
        $this->auditoria->registrarAccion($request, 'comisiones.v2.cerrar', 'tbl_comision_v2_periodos_cmp', (string) $periodo->cmp_id, [
            'periodo' => $datos['periodo'],
            'vendedores' => $total,
            'antes' => $antes,
            'despues' => $this->snapshotPeriodo($periodo),
        ]);
        return back()->with('success', "Periodo cerrado con resultados definitivos para {$total} vendedores.");
    }

    public function guardarDepartamento(Request $request)
    {
        $datos = $request->validate(['nombre' => ['required', 'string', 'max:120']]);
        $clave = Str::of($datos['nombre'])->ascii()->upper()->slug('_')->limit(40, '')->toString();
        if ($clave === '') {
            throw ValidationException::withMessages(['nombre' => 'Escribe un nombre válido.']);
        }
        $request->validate(['nombre' => [Rule::unique('tbl_comision_v2_departamentos_cmd', 'cmd_nombre')->where('cmd_deleted', false)]]);
        $departamento = ComisionV2Departamento::query()->create([
            'cmd_clave' => $clave,
            'cmd_nombre' => trim($datos['nombre']),
            'cmd_estatus' => 'activo',
            'cmd_created_by_usr_id' => $request->user()->usr_id,
            'cmd_updated_by_usr_id' => $request->user()->usr_id,
        ]);
        $this->auditoria->registrarAccion($request, 'comisiones.v2.departamento_crear', 'tbl_comision_v2_departamentos_cmd', (string) $departamento->cmd_id, ['nombre' => $departamento->cmd_nombre]);
        return back()->with('success', 'Departamento creado. Ya puedes asignarle líneas y vendedores.');
    }

    public function alternarDepartamento(Request $request, ComisionV2Departamento $departamento)
    {
        $datos = $request->validate(['estatus' => ['required', Rule::in(['activo', 'inactivo'])]]);
        if ($datos['estatus'] === 'inactivo') {
            $enUso = DB::table('tbl_comision_v2_periodo_departamentos_cpd as cpd')
                ->join('tbl_comision_v2_periodos_cmp as cmp', 'cmp.cmp_id', '=', 'cpd.cpd_cmp_id')
                ->where('cpd.cpd_cmd_id', $departamento->cmd_id)->where('cmp.cmp_estatus', '!=', 'cerrado')->exists();
            if ($enUso) {
                throw ValidationException::withMessages(['estatus' => 'No puedes retirar un departamento usado por un periodo abierto.']);
            }
        }
        $antes = ['nombre' => $departamento->cmd_nombre, 'estatus' => $departamento->cmd_estatus];
        $departamento->update(['cmd_estatus' => $datos['estatus'], 'cmd_updated_by_usr_id' => $request->user()->usr_id]);
        $this->auditoria->registrarAccion($request, 'comisiones.v2.departamento_estado', 'tbl_comision_v2_departamentos_cmd', (string) $departamento->cmd_id, [
            'antes' => $antes,
            'despues' => ['nombre' => $departamento->cmd_nombre, 'estatus' => $departamento->cmd_estatus],
        ]);
        return back()->with('success', 'Estado del departamento actualizado.');
    }

    private function snapshotPeriodo(?ComisionV2Periodo $periodo): ?array
    {
        if (! $periodo) {
            return null;
        }
        $periodo->load(['almacenes', 'departamentos.participantes', 'participantes']);
        $lineas = DB::table('tbl_comision_v2_periodo_lineas_cml')
            ->where('cml_cmp_id', $periodo->cmp_id)
            ->get(['cml_cpd_id', 'cml_lna_id', 'cml_linea_nombre'])
            ->groupBy('cml_cpd_id');

        return [
            'estado' => $periodo->cmp_estatus,
            'almacenes' => $periodo->almacenes->pluck('alm_id')->map(fn ($id) => (int) $id)->values()->all(),
            'departamentos' => $periodo->departamentos->map(fn ($departamento) => [
                'id' => (int) $departamento->cpd_cmd_id,
                'nombre' => $departamento->cpd_departamento_nombre,
                'incremento' => (float) $departamento->cpd_incremento_meta,
                'meta_comun' => $departamento->cpd_meta_comun !== null ? (float) $departamento->cpd_meta_comun : null,
                'meta_sugerida' => $departamento->cpd_meta_sugerida !== null ? (float) $departamento->cpd_meta_sugerida : null,
                'vendedores_congelados' => (int) $departamento->cpd_vendedores_congelados,
                'lineas' => ($lineas->get($departamento->cpd_id) ?? collect())->map(fn ($linea) => [
                    'id' => (int) $linea->cml_lna_id,
                    'nombre' => $linea->cml_linea_nombre,
                ])->values()->all(),
            ])->values()->all(),
            'vendedores' => $periodo->participantes->map(fn ($participante) => [
                'usuario_id' => (int) $participante->cpt_usr_id,
                'departamento_periodo_id' => (int) $participante->cpt_cpd_id,
                'numero' => $participante->cpt_numero_vendedor,
                'meta' => (float) $participante->cpt_meta_individual,
                'tasa' => (float) $participante->cpt_tasa_comision,
                'motivo' => $participante->cpt_motivo_ajuste,
            ])->values()->all(),
        ];
    }

    private function resolverPeriodo(Request $request, string $periodo): ComisionV2Periodo
    {
        $registro = ComisionV2Periodo::query()
            ->where('cmp_scl_id', $this->sucursalActiva($request))
            ->whereDate('cmp_periodo', Carbon::createFromFormat('Y-m', $periodo)->startOfMonth()->toDateString())
            ->first();
        if (! $registro) {
            throw ValidationException::withMessages(['periodo' => 'Primero guarda la configuración del periodo.']);
        }
        return $registro;
    }

    private function sucursalActiva(Request $request): int
    {
        $id = (int) $request->session()->get('sucursal_activa_id');
        abort_if($id <= 0, 422, 'No hay una sucursal activa.');
        return $id;
    }

    private function periodoTexto(string $periodo): string
    {
        try {
            return Carbon::createFromFormat('Y-m', $periodo)->format('Y-m');
        } catch (\Throwable) {
            return now()->format('Y-m');
        }
    }

    private function submenus(): array
    {
        return [
            ['key' => 'sucursales', 'label' => 'Sucursales', 'route' => route('desktop.operacion.gestion_configuraciones.sucursales.index')],
            ['key' => 'almacenes', 'label' => 'Almacenes', 'route' => route('desktop.operacion.gestion_configuraciones.almacenes.index')],
            ['key' => 'tipos_almacen', 'label' => 'Tipos de almacén', 'route' => route('desktop.operacion.gestion_configuraciones.tipos_almacen.index')],
            ['key' => 'cajas', 'label' => 'Cajas', 'route' => route('desktop.operacion.gestion_configuraciones.cajas.index')],
            ['key' => 'clientes', 'label' => 'Clientes', 'route' => route('desktop.operacion.gestion_configuraciones.clientes.index')],
            ['key' => 'comisiones', 'label' => 'Metas y comisiones', 'route' => route('desktop.operacion.gestion_configuraciones.comisiones.index')],
            ['key' => 'ticket', 'label' => 'Personalizar ticket', 'route' => route('desktop.operacion.gestion_configuraciones.ticket.index')],
            ['key' => 'impresoras', 'label' => 'Impresoras', 'route' => route('desktop.operacion.gestion_configuraciones.impresoras.index')],
        ];
    }
}
