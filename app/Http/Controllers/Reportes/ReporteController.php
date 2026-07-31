<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reportes\ReportesFiltroRequest;
use App\Models\Almacen;
use App\Models\Caja;
use App\Models\Usuario;
use App\Services\AuditoriaService;
use App\Services\Reportes\ReporteConsultaService;
use App\Services\Reportes\ReporteExportacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ReporteController extends Controller
{
    public function __construct(
        private readonly ReporteConsultaService $consultas,
        private readonly ReporteExportacionService $exportador,
        private readonly AuditoriaService $auditoria,
    ) {}

    public function index(Request $request)
    {
        return view('desktop.reportes', ['catalogo' => $this->consultas->catalogo(), 'reporte' => null]);
    }

    public function show(Request $request, string $reporte)
    {
        $definicion = $this->resolverYAutorizar($request, $reporte);
        $sucursalId = $this->sucursalActiva($request);
        return view('desktop.reportes', [
            'catalogo' => $this->consultas->catalogo(), 'reporte' => $definicion,
            'cajas' => Caja::query()->where('caj_scl_id', $sucursalId)->where('caj_deleted', false)->orderBy('caj_nombre')->get(['caj_id','caj_nombre']),
            'almacenes' => Almacen::query()->where('alm_scl_id', $sucursalId)->where('alm_deleted', false)->where('alm_estatus','activo')->orderBy('alm_nombre')->get(['alm_id','alm_nombre']),
            'usuarios' => Usuario::query()->whereHas('sucursales', fn($q) => $q->where('tbl_sucursales_scl.scl_id', $sucursalId))->where('usr_estatus','activo')->orderBy('usr_nombre')->get(['usr_id','usr_nombre']),
        ]);
    }

    public function data(ReportesFiltroRequest $request, string $reporte): JsonResponse
    {
        $def = $this->resolverYAutorizar($request, $reporte);
        $resultado = $this->consultas->consultar($reporte, $request->user(), $this->sucursalActiva($request), $request->validated());
        $this->auditar($request, $resultado, 'consulta');
        return response()->json($resultado);
    }

    public function exportar(ReportesFiltroRequest $request, string $reporte, string $formato)
    {
        abort_unless(in_array($formato, ['csv','xlsx','pdf'], true), 404);
        abort_unless($request->user()?->tienePermiso('reportes.exportar'), 403, 'No tienes permiso para exportar reportes.');
        $this->resolverYAutorizar($request, $reporte);
        $resultado = $this->consultas->consultar($reporte, $request->user(), $this->sucursalActiva($request), $request->validated(), true);
        $this->auditar($request, $resultado, 'exportar_'.$formato);
        $nombre = str($reporte.'-'.$resultado['desde'].'-'.$resultado['hasta'])->slug();
        if ($formato === 'csv') return response()->streamDownload(function () use ($resultado) {
            $h=fopen('php://output','wb'); fwrite($h,"\xEF\xBB\xBF"); fputcsv($h,$resultado['encabezados']); foreach($resultado['rows'] as $row) fputcsv($h,array_values((array)$row)); fclose($h);
        }, "$nombre.csv", ['Content-Type'=>'text/csv; charset=UTF-8']);
        $contenido = $formato === 'xlsx' ? $this->exportador->xlsx($resultado) : $this->exportador->pdf($resultado);
        return response($contenido, 200, ['Content-Type'=>$formato==='xlsx'?'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':'application/pdf','Content-Disposition'=>"attachment; filename=\"$nombre.$formato\""]);
    }

    private function resolverYAutorizar(Request $request, string $slug): array
    {
        try { $def = $this->consultas->definicion($slug); } catch (InvalidArgumentException) { abort(404); }
        abort_unless($request->user()?->tienePermiso($def['permiso']), 403, 'No tienes permiso para consultar este reporte.');
        return $def;
    }
    private function sucursalActiva(Request $request): int { $id=(int)$request->session()->get('sucursal_activa_id'); abort_if($id<=0,422,'No hay una sucursal activa.'); return $id; }
    private function auditar(Request $request,array $r,string $accion): void { $this->auditoria->registrarAccion($request,'reportes.'.$accion,'reporte',$r['slug'],['reporte'=>$r['slug'],'desde'=>$r['desde'],'hasta'=>$r['hasta'],'resultado'=>$r['total_registros']]); }
}
