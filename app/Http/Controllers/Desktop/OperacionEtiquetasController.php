<?php

namespace App\Http\Controllers\Desktop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operacion\Etiquetas\GenerarEtiquetasRecepcionRequest;
use App\Http\Requests\Operacion\Etiquetas\StoreEtiquetaConfiguracionRequest;
use App\Http\Requests\Operacion\Etiquetas\StoreEtiquetaFormatoRequest;
use App\Http\Requests\Operacion\Etiquetas\StoreEtiquetaPlantillaRequest;
use App\Http\Requests\Operacion\Etiquetas\StoreEtiquetaUnidadReglaRequest;
use App\Models\EtiquetaFormato;
use App\Models\EtiquetaLineaConfiguracion;
use App\Models\EtiquetaPlantilla;
use App\Models\EtiquetaUnidadRegla;
use App\Models\Linea;
use App\Models\UnidadMedida;
use App\Services\Operacion\Comercial\EtiquetadoRecepcionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class OperacionEtiquetasController extends Controller
{
    public function __construct(private readonly EtiquetadoRecepcionService $etiquetas) {}
    public function index()
    {
        $historial = DB::table('tbl_etiqueta_impresiones_eim as eim')
            ->join('tbl_recepciones_mercancia_rme as rme', 'rme.rme_id', '=', 'eim.eim_rme_id')
            ->orderByDesc('eim.eim_id')
            ->limit(100)
            ->get(['eim.eim_id', 'eim.eim_modo', 'eim.eim_estatus', 'eim.eim_total_etiquetas', 'eim.eim_generado_at', 'rme.rme_folio']);
        $archivos = DB::table('tbl_etiqueta_impresion_archivos_eia')
            ->whereIn('eia_eim_id', $historial->pluck('eim_id'))
            ->orderBy('eia_id')
            ->get(['eia_id', 'eia_eim_id', 'eia_nombre'])
            ->groupBy('eia_eim_id');
        $historial->each(fn ($impresion) => $impresion->archivos = $archivos->get($impresion->eim_id, collect()));

        return view('desktop.operacion.etiquetas.index', [
            'formatos' => EtiquetaFormato::query()->withCount('configuracionesLinea')->orderBy('etf_nombre')->get(),
            'plantillas' => EtiquetaPlantilla::query()->orderBy('etp_nombre')->get(),
            'lineas' => Linea::query()->where('lna_estatus', 'activo')->orderBy('lna_nombre')->get(),
            'configuraciones' => EtiquetaLineaConfiguracion::query()->with(['linea', 'formato', 'plantilla'])->orderBy('elc_id', 'desc')->get(),
            'unidades' => UnidadMedida::query()->where('umd_estatus', 'activo')->orderBy('umd_nombre')->get(),
            'reglas' => EtiquetaUnidadRegla::query()->get()->keyBy('eur_umd_id'),
            'historial' => $historial,
        ]);
    }
    public function storeFormato(StoreEtiquetaFormatoRequest $request): JsonResponse { $data=$request->validated();$data['etf_created_by_usr_id']=$request->user()?->usr_id;$data['etf_updated_by_usr_id']=$request->user()?->usr_id; $f=EtiquetaFormato::query()->create($data);return response()->json(['message'=>'Formato creado.','data'=>$f]); }
    public function updateFormato(StoreEtiquetaFormatoRequest $request, int $formato): JsonResponse { $f=EtiquetaFormato::query()->findOrFail($formato);$f->update([...$request->validated(),'etf_updated_by_usr_id'=>$request->user()?->usr_id]);return response()->json(['message'=>'Formato actualizado.','data'=>$f]); }
    public function storePlantilla(StoreEtiquetaPlantillaRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['etp_created_by_usr_id'] = $request->user()?->usr_id;
        $data['etp_updated_by_usr_id'] = $request->user()?->usr_id;

        return response()->json([
            'message' => 'Plantilla creada.',
            'data' => EtiquetaPlantilla::query()->create($data),
        ]);
    }

    public function updatePlantilla(StoreEtiquetaPlantillaRequest $request, int $plantilla): JsonResponse
    {
        $model = EtiquetaPlantilla::query()->findOrFail($plantilla);
        $model->update([
            ...$request->validated(),
            'etp_updated_by_usr_id' => $request->user()?->usr_id,
        ]);

        return response()->json(['message' => 'Plantilla actualizada.', 'data' => $model]);
    }
    public function storeConfiguracion(StoreEtiquetaConfiguracionRequest $request): JsonResponse { $data=$request->validated();$data['elc_created_by_usr_id']=$request->user()?->usr_id;$data['elc_updated_by_usr_id']=$request->user()?->usr_id; $c=EtiquetaLineaConfiguracion::query()->updateOrCreate(['elc_lna_id'=>$data['elc_lna_id'],'elc_deleted'=>false],$data);return response()->json(['message'=>'Asignación guardada.','data'=>$c]); }
    public function storeRegla(StoreEtiquetaUnidadReglaRequest $request): JsonResponse
    {
        $data = $request->validated() + [
            'eur_estatus' => 'activo',
            'eur_updated_by_usr_id' => $request->user()?->usr_id,
        ];
        $regla = EtiquetaUnidadRegla::query()->updateOrCreate(
            ['eur_umd_id' => $data['eur_umd_id'], 'eur_deleted' => false],
            $data,
        );

        return response()->json(['message' => 'Regla de unidad guardada.', 'data' => $regla]);
    }
    public function analizarRecepcion(int $recepcion): JsonResponse { return response()->json($this->etiquetas->analizar($recepcion)); }
    public function generarRecepcion(GenerarEtiquetasRecepcionRequest $request, int $recepcion): JsonResponse { return response()->json(['message'=>'Etiquetas generadas.','data'=>$this->etiquetas->generar($request,$recepcion,$request->validated('modo'))]); }
    public function verArchivo(int $archivo)
    {
        $row = DB::table('tbl_etiqueta_impresion_archivos_eia')->where('eia_id', $archivo)->first();
        abort_unless($row && Storage::disk('local')->exists($row->eia_path), 404);

        return Storage::disk('local')->response(
            $row->eia_path,
            $row->eia_nombre,
            ['Content-Type' => $row->eia_mime ?: 'application/pdf'],
            'inline',
        );
    }
    public function descargarArchivo(int $archivo) { $row=DB::table('tbl_etiqueta_impresion_archivos_eia')->where('eia_id',$archivo)->first();abort_unless($row && Storage::disk('local')->exists($row->eia_path),404);return Storage::disk('local')->download($row->eia_path,$row->eia_nombre,['Content-Type'=>$row->eia_mime]); }
    public function descargarZip(int $impresion)
    {
        $files = DB::table('tbl_etiqueta_impresion_archivos_eia')->where('eia_eim_id', $impresion)->get();
        abort_if($files->isEmpty(), 404);
        $temp = tempnam(sys_get_temp_dir(), 'etiquetas-'); $zipPath = $temp . '.zip'; @unlink($temp);
        $zip = new ZipArchive(); abort_unless($zip->open($zipPath, ZipArchive::CREATE) === true, 500);
        foreach ($files as $file) if (Storage::disk('local')->exists($file->eia_path)) $zip->addFromString($file->eia_nombre, Storage::disk('local')->get($file->eia_path));
        $zip->close();
        return response()->download($zipPath, 'etiquetas-impresion-'.$impresion.'.zip', ['Content-Type'=>'application/zip'])->deleteFileAfterSend(true);
    }
}
