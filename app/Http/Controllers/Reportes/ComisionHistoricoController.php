<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reportes\GuardarHistoricoVentasRequest;
use App\Models\Almacen;
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
        $filtros = $request->validate(['mes' => ['nullable', 'date_format:Y-m'], 'editar' => ['nullable', 'integer', 'min:1']]);
        $edicion = empty($filtros['editar']) ? null : DB::table('tbl_comision_historicos_chv')
            ->where('chv_scl_id', $sucursalId)->where('chv_id', $filtros['editar'])->first();
        abort_if(! empty($filtros['editar']) && ! $edicion, 404);
        $mes = $filtros['mes'] ?? ($edicion ? substr($edicion->chv_periodo, 0, 7) : now()->subYear()->format('Y-m'));
        $registros = DB::table('tbl_comision_historicos_chv as chv')
            ->join('tbl_almacenes_alm as alm', 'alm.alm_id', '=', 'chv.chv_alm_id')
            ->join('tbl_lineas_lna as lna', 'lna.lna_id', '=', 'chv.chv_lna_id')
            ->where('chv.chv_scl_id', $sucursalId)
            ->whereDate('chv.chv_periodo', Carbon::createFromFormat('Y-m', $mes)->startOfMonth()->toDateString())
            ->orderBy('alm.alm_nombre')->orderBy('lna.lna_nombre')
            ->get(['chv.*', 'alm.alm_nombre', 'lna.lna_nombre']);

        return view('desktop.operacion.gestion_configuraciones.historico_ventas', [
            'mes' => $mes, 'edicion' => $edicion, 'registros' => $registros,
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

        return redirect()->route('desktop.operacion.gestion_configuraciones.comisiones.historico.index', ['mes' => $datos['periodo']])
            ->with('success', 'Histórico guardado. Estará disponible al guardar la configuración de metas del mismo mes del año siguiente.');
    }

    private function sucursalActiva(Request $request): int
    {
        $id = (int) $request->session()->get('sucursal_activa_id');
        abort_if($id <= 0, 422, 'No hay una sucursal activa.');

        return $id;
    }
}
