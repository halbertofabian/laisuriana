<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reportes\GuardarHistoricoVentasRequest;
use App\Models\Almacen;
use App\Models\BitacoraAccion;
use App\Models\Linea;
use App\Services\AuditoriaService;
use App\Services\Reportes\ComisionHistoricoService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComisionHistoricoController extends Controller
{
    public function index(Request $request)
    {
        $sucursalId = $this->sucursalActiva($request);
        $filtros = $request->validate([
            'mes' => ['nullable', 'date_format:Y-m'],
            'editar' => ['nullable', 'integer', 'min:1'],
            'volver' => ['nullable', 'date_format:Y-m'],
            'almacen' => ['nullable', 'integer', 'min:1'],
            'linea' => ['nullable', 'integer', 'min:1'],
        ]);
        $edicion = empty($filtros['editar']) ? null : DB::table('tbl_comision_historicos_chv as chv')
            ->leftJoin('tbl_usuarios_usr as usr', 'usr.usr_id', '=', 'chv.chv_updated_by_usr_id')
            ->where('chv.chv_scl_id', $sucursalId)->where('chv.chv_id', $filtros['editar'])
            ->first(['chv.*', 'usr.usr_nombre as actualizado_por']);
        abort_if(! empty($filtros['editar']) && ! $edicion, 404);
        $volver = $filtros['volver'] ?? null;
        $mes = $filtros['mes']
            ?? ($edicion ? substr($edicion->chv_periodo, 0, 7) : null)
            ?? ($volver ? Carbon::createFromFormat('Y-m', $volver)->subYearNoOverflow()->format('Y-m') : now()->subYear()->format('Y-m'));
        $registros = DB::table('tbl_comision_historicos_chv as chv')
            ->join('tbl_almacenes_alm as alm', 'alm.alm_id', '=', 'chv.chv_alm_id')
            ->join('tbl_lineas_lna as lna', 'lna.lna_id', '=', 'chv.chv_lna_id')
            ->leftJoin('tbl_usuarios_usr as usr', 'usr.usr_id', '=', 'chv.chv_updated_by_usr_id')
            ->where('chv.chv_scl_id', $sucursalId)
            ->whereDate('chv.chv_periodo', Carbon::createFromFormat('Y-m', $mes)->startOfMonth()->toDateString())
            ->orderBy('alm.alm_nombre')->orderBy('lna.lna_nombre')
            ->get(['chv.*', 'alm.alm_nombre', 'lna.lna_nombre', 'usr.usr_nombre as actualizado_por']);
        // Combinaciones ya capturadas para advertir duplicados antes de enviar el formulario.
        $existentes = DB::table('tbl_comision_historicos_chv')
            ->where('chv_scl_id', $sucursalId)
            ->get(['chv_id', 'chv_periodo', 'chv_alm_id', 'chv_lna_id', 'chv_ventas_netas', 'chv_autoservicio', 'chv_version'])
            ->mapWithKeys(fn ($fila) => [substr($fila->chv_periodo, 0, 7).'|'.$fila->chv_alm_id.'|'.$fila->chv_lna_id => [
                'id' => (int) $fila->chv_id,
                'ventas' => (float) $fila->chv_ventas_netas,
                'autoservicio' => (float) $fila->chv_autoservicio,
                'version' => (int) $fila->chv_version,
            ]]);
        $cambios = $edicion ? BitacoraAccion::query()
            ->leftJoin('tbl_usuarios_usr as usr', 'usr.usr_id', '=', 'tbl_bitacora_acciones_bac.bac_usr_id')
            ->where('bac_accion', 'comisiones.historico.guardar')
            ->where('bac_entidad', 'tbl_comision_historicos_chv')
            ->where('bac_entidad_id', (string) $edicion->chv_id)
            ->orderByDesc('bac_id')->limit(10)
            ->get(['tbl_bitacora_acciones_bac.*', 'usr.usr_nombre']) : collect();

        return view('desktop.operacion.gestion_configuraciones.historico_ventas', [
            'mes' => $mes, 'edicion' => $edicion, 'registros' => $registros, 'volver' => $volver,
            'existentes' => $existentes, 'cambios' => $cambios,
            'prefill' => ['almacen' => $filtros['almacen'] ?? null, 'linea' => $filtros['linea'] ?? null],
            'sucursalNombre' => DB::table('tbl_sucursales_scl')->where('scl_id', $sucursalId)->value('scl_nombre'),
            'almacenes' => Almacen::query()->where('alm_scl_id', $sucursalId)->where('alm_deleted', false)->orderBy('alm_nombre')->get(),
            'lineas' => Linea::query()->where('lna_deleted', false)->orderBy('lna_nombre')->get(),
        ]);
    }

    public function store(GuardarHistoricoVentasRequest $request, ComisionHistoricoService $historico, AuditoriaService $auditoria)
    {
        $datos = $request->validated();
        DB::transaction(function () use ($request, $datos, $historico, $auditoria) {
            $cambio = $historico->guardar($datos, $this->sucursalActiva($request), (int) $request->user()->usr_id);
            $auditoria->registrarAccion($request, 'comisiones.historico.guardar', 'tbl_comision_historicos_chv', (string) $cambio['id'], [
                'antes' => $cambio['antes'], 'despues' => $cambio['despues'], 'motivo' => $datos['motivo'] ?? null,
            ]);
        });
        $destino = Carbon::createFromFormat('Y-m', $datos['periodo'])->addYearNoOverflow()->locale('es')->isoFormat('MMMM [de] YYYY');

        return redirect()->route('desktop.operacion.gestion_configuraciones.comisiones.historico.index', array_filter(['mes' => $datos['periodo'], 'volver' => $datos['volver'] ?? null]))
            ->with('success', "Histórico guardado. Servirá de referencia para las metas de {$destino}; las metas ya guardadas se actualizan al guardar de nuevo la configuración de ese mes.");
    }

    private function sucursalActiva(Request $request): int
    {
        $id = (int) $request->session()->get('sucursal_activa_id');
        abort_if($id <= 0, 422, 'No hay una sucursal activa.');

        return $id;
    }
}
