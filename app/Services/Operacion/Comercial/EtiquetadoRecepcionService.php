<?php

namespace App\Services\Operacion\Comercial;

use App\Models\EtiquetaFormato;
use App\Models\EtiquetaLineaConfiguracion;
use App\Models\EtiquetaUnidadRegla;
use App\Models\RecepcionMercancia;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use TCPDF;

class EtiquetadoRecepcionService
{
    public function __construct(
        private readonly AuditoriaService $auditoriaService,
        private readonly EtiquetaProductoRenderer $renderer,
    ) {}

    public function analizar(int $recepcionId, array $etiquetasPorDetalle = []): array
    {
        $recepcion = RecepcionMercancia::query()->with(['sucursal:scl_id,scl_nombre', 'detalle.sku.valoresAtributo.atributo', 'detalle.sku.producto.linea', 'detalle.sku.producto.unidad', 'detalle.sku.producto.marca:mrc_id,mrc_nombre'])->findOrFail($recepcionId);
        $errores = []; $grupos = [];
        foreach ($recepcion->detalle as $detalle) {
            $sku = $detalle->sku; $producto = $sku?->producto;
            if (!$producto) { $errores[] = $this->incidencia($detalle, 'producto', 'El detalle no tiene producto asociado.'); continue; }
            if (!$producto->linea) { $errores[] = $this->incidencia($detalle, 'linea', 'El producto no tiene línea configurada.'); continue; }
            $config = EtiquetaLineaConfiguracion::query()->with(['formato','plantilla'])->where('elc_lna_id', $producto->prd_lna_id)->where('elc_estatus','activo')->first();
            if (!$config || !$config->formato || $config->formato->etf_estatus !== 'activo' || !$config->plantilla || $config->plantilla->etp_estatus !== 'activo') { $errores[] = $this->incidencia($detalle, 'configuracion', 'La línea '.$producto->linea->lna_nombre.' no tiene formato y plantilla activos.'); continue; }
            $regla = EtiquetaUnidadRegla::query()->where('eur_umd_id', $producto->prd_umd_id)->where('eur_estatus','activo')->first();
            if (!$regla) { $errores[] = $this->incidencia($detalle, 'unidad', 'La unidad '.($producto->unidad?->umd_nombre ?: 'sin unidad').' no tiene regla de etiquetas.'); continue; }
            $cantidad = (float) $detalle->rmd_cantidad;
            $esPorDetalle = $regla->eur_regla === 'por_detalle_recepcion';
            $etiquetas = $esPorDetalle
                ? max(1, (int) ($etiquetasPorDetalle[$detalle->rmd_id] ?? 1))
                : (int) $cantidad;
            if ($cantidad <= 0 || ($regla->eur_regla === 'por_unidad_recibida' && floor($cantidad) !== $cantidad)) { $errores[] = $this->incidencia($detalle, 'cantidad', 'La cantidad recibida no es válida para la regla de unidad.'); continue; }
            $campos = (array) $config->plantilla->etp_campos;
            if (in_array('codigo_barras', $campos, true) && trim((string) ($sku->psk_codigo_barras ?: $sku->psk_codigo)) === '') { $errores[] = $this->incidencia($detalle, 'codigo_barras', 'La plantilla requiere código de barras y el SKU no lo tiene.'); continue; }
            $id = (int) $config->elc_etf_id;
            $grupos[$id] ??= ['formato'=>$config->formato, 'plantilla'=>$config->plantilla, 'lineas'=>[], 'items'=>[], 'productos'=>0, 'etiquetas'=>0];
            $grupos[$id]['lineas'][(int) $producto->prd_lna_id] = $producto->linea->lna_nombre;
            $grupos[$id]['items'][] = compact('detalle','sku','producto','config','etiquetas','esPorDetalle');
            $grupos[$id]['productos']++; $grupos[$id]['etiquetas'] += $etiquetas;
        }
        foreach ($grupos as &$grupo) {
            usort($grupo['items'], function (array $a, array $b): int {
                return [$this->textoOrdenProducto($a), $this->textoOrdenColor($a), (string) $a['sku']->psk_codigo]
                    <=> [$this->textoOrdenProducto($b), $this->textoOrdenColor($b), (string) $b['sku']->psk_codigo];
            });
        }
        unset($grupo);

        $ajustesPorDetalle = collect($grupos)
            ->flatMap(fn (array $grupo) => $grupo['items'])
            ->filter(fn (array $item) => $item['esPorDetalle'])
            ->map(fn (array $item) => [
                'rmd_id' => $item['detalle']->rmd_id,
                'sku' => $item['sku']->psk_codigo,
                'nombre_producto' => $item['sku']->psk_nombre ?: $item['producto']->prd_nombre,
                'cantidad_recibida' => $item['detalle']->rmd_cantidad,
                'unidad' => $item['producto']->unidad?->umd_nombre,
                'etiquetas' => $item['etiquetas'],
            ])
            ->values()
            ->all();

        return ['recepcion'=>$recepcion, 'grupos'=>array_values($grupos), 'ajustes_por_detalle'=>$ajustesPorDetalle, 'errores'=>$errores, 'puede_generar'=>empty($errores) && !empty($grupos)];
    }

    public function generar(Request $request, int $recepcionId, string $modo, array $etiquetasPorDetalle = []): array
    {
        $analisis = $this->analizar($recepcionId, $etiquetasPorDetalle);
        if (!$analisis['puede_generar']) throw ValidationException::withMessages(['etiquetas' => collect($analisis['errores'])->pluck('mensaje')->all()]);
        return DB::transaction(function () use ($request, $analisis, $modo, $recepcionId) {
            $historial = DB::table('tbl_etiqueta_impresiones_eim')->insertGetId(['eim_rme_id'=>$recepcionId,'eim_usuario_id'=>optional($request->user())->usr_id,'eim_modo'=>$modo,'eim_estatus'=>'procesando','eim_total_etiquetas'=>collect($analisis['grupos'])->sum('etiquetas'),'eim_total_productos'=>collect($analisis['grupos'])->sum('productos'),'eim_resumen'=>json_encode($this->resumen($analisis)),'eim_created_at'=>now(),'eim_updated_at'=>now()]);
            $pdfs = $modo === 'unico' ? [['formato'=>null,'content'=>$this->pdfMixto($analisis['grupos']),'nombre'=>'recepcion-'.$analisis['recepcion']->rme_folio.'-etiquetas.pdf']] : collect($analisis['grupos'])->map(fn ($g) => ['formato'=>$g['formato'],'content'=>$this->pdfGrupo($g),'nombre'=>'recepcion-'.$analisis['recepcion']->rme_folio.'-'.Str::slug($g['formato']->etf_nombre).'.pdf'])->all();
            $archivos=[]; foreach ($pdfs as $pdf) { $path='etiquetas/'.$recepcionId.'/'.$historial.'/'.$pdf['nombre']; Storage::disk('local')->put($path,$pdf['content']); $archivoId=DB::table('tbl_etiqueta_impresion_archivos_eia')->insertGetId(['eia_eim_id'=>$historial,'eia_etf_id'=>$pdf['formato']?->etf_id,'eia_nombre'=>$pdf['nombre'],'eia_path'=>$path,'eia_tamano_bytes'=>strlen($pdf['content']),'eia_created_at'=>now(),'eia_updated_at'=>now()]); $archivos[]=['id'=>$archivoId,'nombre'=>$pdf['nombre']]; }
            foreach ($analisis['grupos'] as $g) foreach ($g['items'] as $i) DB::table('tbl_etiqueta_impresion_detalles_eid')->insert(['eid_eim_id'=>$historial,'eid_rmd_id'=>$i['detalle']->rmd_id,'eid_psk_id'=>$i['sku']->psk_id,'eid_etf_id'=>$g['formato']->etf_id,'eid_etp_id'=>$i['config']->elc_etp_id,'eid_cantidad_recibida'=>$i['detalle']->rmd_cantidad,'eid_etiquetas'=>$i['etiquetas'],'eid_snapshot'=>json_encode($this->datosEtiqueta($analisis['recepcion'],$i)),'eid_created_at'=>now(),'eid_updated_at'=>now()]);
            DB::table('tbl_etiqueta_impresiones_eim')->where('eim_id',$historial)->update(['eim_estatus'=>'completado','eim_generado_at'=>now(),'eim_updated_at'=>now()]);
            $this->auditoriaService->registrarAccion($request, 'etiquetas.recepcion.generar', 'tbl_recepciones_mercancia_rme', (string) $recepcionId, ['eim_id'=>$historial,'modo'=>$modo]);
            return ['eim_id'=>$historial,'archivos'=>$archivos];
        });
    }

    private function pdfMixto(array $grupos): string { $first=$grupos[0]['formato']; $pdf=$this->nuevoPdf($first); foreach($grupos as $g) $this->agregarGrupo($pdf,$g); return $pdf->Output('', 'S'); }
    private function pdfGrupo(array $grupo): string { $pdf=$this->nuevoPdf($grupo['formato']); $this->agregarGrupo($pdf,$grupo); return $pdf->Output('', 'S'); }
    private function nuevoPdf(EtiquetaFormato $f): TCPDF { $pdf=new TCPDF('P','mm',[(float)$f->etf_ancho_mm,(float)$f->etf_alto_mm],true,'UTF-8',false,false); $pdf->setPrintHeader(false);$pdf->setPrintFooter(false);$pdf->SetMargins(0,0,0);$pdf->SetAutoPageBreak(false,0);$pdf->SetCompression(false);$pdf->SetTextColor(0,0,0);return $pdf; }
    private function agregarGrupo(TCPDF $pdf, array $grupo): void
    {
        $formato = $grupo['formato'];
        [$anchoEtiqueta, $altoEtiqueta] = $this->dimensionesEtiqueta($formato);
        $etiquetas = [];
        foreach ($grupo['items'] as $item) {
            for ($indice = 0; $indice < $item['etiquetas']; $indice++) {
                $etiquetas[] = $item;
            }
        }

        if ($formato->etf_tipo_salida !== 'hoja') {
            foreach ($etiquetas as $item) {
                $this->agregarPagina($pdf, $anchoEtiqueta, $altoEtiqueta);
                $this->dibujarEtiqueta($pdf, $formato, $this->camposPlantilla($item), $this->datosEtiqueta(null, $item), 0, 0, $anchoEtiqueta, $altoEtiqueta);
            }

            return;
        }

        $columnas = max(1, (int) $formato->etf_columnas);
        $filas = max(1, (int) $formato->etf_filas);
        $porPagina = $columnas * $filas;
        $separacionHorizontal = (float) $formato->etf_separacion_h_mm;
        $separacionVertical = (float) $formato->etf_separacion_v_mm;
        $anchoPagina = ($columnas * $anchoEtiqueta) + (($columnas - 1) * $separacionHorizontal);
        $altoPagina = ($filas * $altoEtiqueta) + (($filas - 1) * $separacionVertical);

        foreach (array_chunk($etiquetas, $porPagina) as $pagina) {
            $this->agregarPagina($pdf, $anchoPagina, $altoPagina);
            foreach ($pagina as $posicion => $item) {
                $columna = $posicion % $columnas;
                $fila = intdiv($posicion, $columnas);
                $offsetX = $columna * ($anchoEtiqueta + $separacionHorizontal);
                $offsetY = $fila * ($altoEtiqueta + $separacionVertical);
                $this->dibujarEtiqueta($pdf, $formato, $this->camposPlantilla($item), $this->datosEtiqueta(null, $item), $offsetX, $offsetY, $anchoEtiqueta, $altoEtiqueta);
            }
        }
    }

    private function dimensionesEtiqueta(EtiquetaFormato $formato): array
    {
        $ancho = (float) $formato->etf_ancho_mm;
        $alto = (float) $formato->etf_alto_mm;

        return match ($formato->etf_orientacion) {
            'horizontal' => [max($ancho, $alto), min($ancho, $alto)],
            'vertical' => [min($ancho, $alto), max($ancho, $alto)],
            default => [$ancho, $alto],
        };
    }

    private function agregarPagina(TCPDF $pdf, float $ancho, float $alto): void
    {
        $pdf->AddPage($ancho > $alto ? 'L' : 'P', [$ancho, $alto]);
    }

    private function camposPlantilla(array $item): array
    {
        return (array) ($item['config']->plantilla?->etp_campos ?: []);
    }

    private function textoOrdenProducto(array $item): string
    {
        return Str::lower(trim((string) ($item['sku']->psk_nombre ?: $item['producto']->prd_nombre)));
    }

    private function textoOrdenColor(array $item): string
    {
        return Str::lower((string) $item['sku']->valoresAtributo
            ->first(fn ($valor) => Str::lower((string) ($valor->atributo?->atr_nombre ?? '')) === 'color')?->vat_valor);
    }

    private function dibujarEtiqueta(TCPDF $pdf, EtiquetaFormato $f, array $campos, array $d, float $offsetX = 0, float $offsetY = 0, ?float $anchoEtiqueta = null, ?float $altoEtiqueta = null): void
    {
        $this->renderer->dibujar(
            $pdf,
            $d,
            $campos,
            $anchoEtiqueta ?? (float) $f->etf_ancho_mm,
            $altoEtiqueta ?? (float) $f->etf_alto_mm,
            $offsetX,
            $offsetY,
            [
                'izq' => (float) $f->etf_margen_izq_mm,
                'der' => (float) $f->etf_margen_der_mm,
                'sup' => (float) $f->etf_margen_sup_mm,
                'inf' => (float) $f->etf_margen_inf_mm,
            ],
        );
    }
    private function datosEtiqueta($recepcion, array $i): array { $p=$i['producto'];$s=$i['sku'];$attrs=$s->valoresAtributo->mapWithKeys(fn($v)=>[Str::lower($v->atributo?->atr_nombre ?? '')=>$v->vat_valor]);return ['nombre_producto'=>$s->psk_nombre?:$p->prd_nombre,'sku'=>$s->psk_codigo,'codigo_barras'=>$s->psk_codigo_barras?:$s->psk_codigo,'precio'=>'$'.number_format((float)$s->psk_precio,2),'marca'=>$p->marca?->mrc_nombre,'linea'=>$p->linea?->lna_nombre,'unidad'=>$p->unidad?->umd_nombre,'cantidad'=>(string)$i['detalle']->rmd_cantidad,'talla'=>$attrs['talla']??'','color'=>$attrs['color']??'','sucursal'=>$recepcion?->sucursal?->scl_nombre ?? '','folio_recepcion'=>$recepcion?->rme_folio ?? '','fecha_recepcion'=>optional($recepcion?->rme_fecha_captura)->format('d/m/Y') ?? '','fecha_impresion'=>now()->format('d/m/Y')]; }
    private function resumen(array $a): array { return collect($a['grupos'])->map(fn($g)=>['formato'=>$g['formato']->etf_nombre,'lineas'=>array_values($g['lineas']),'productos'=>$g['productos'],'etiquetas'=>$g['etiquetas']])->all(); }
    private function incidencia($d,string $tipo,string $mensaje): array{return ['rmd_id'=>$d->rmd_id,'sku'=>$d->sku?->psk_codigo,'tipo'=>$tipo,'mensaje'=>$mensaje];}
}
