<?php

namespace App\Services\Operacion;

use App\Models\MovimientoInventario;
use App\Models\RecepcionMercancia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Ticket HORIZONTAL de recepción de mercancía — raster GD rotado -90°.
 *
 * Layout por secciones:
 *   Sección 1  → [Producto | Color | t1..t4   | Total]  + metadatos 1
 *   Sección 2+ → [         t5..t12            | Total]  + metadatos 2/3
 *   Última sec → [...tallas | Total] + bloque de totales monetarios
 *
 * Todas las secciones tienen EXACTAMENTE el mismo $headerH y $rowH,
 * por lo que los renglones quedan alineados al pegar las tiras.
 *
 * .env:
 *   THERMAL_PRINTER_NAME
 *   THERMAL_HORIZONTAL_DPI               (default 203)
 *   THERMAL_HORIZONTAL_PAPER_MM          (default 72)
 *   THERMAL_HORIZONTAL_FONT_SCALE        (default 1.0)
 *   THERMAL_HORIZONTAL_MARGIN_PX         (default 8)
 *   THERMAL_HORIZONTAL_FIRST_TALLAS      (default 4  — tallas en sección 1)
 *   THERMAL_HORIZONTAL_NEXT_TALLAS       (default 8  — tallas en secciones 2+)
 *   THERMAL_HORIZONTAL_DEBUG_IMAGE       (default false)
 */
class EscposRecepcionHorizontalService
{
    // ── Fuentes base en pt (× $fscale al renderizar) ─────────────────────
    private const F_TITLE  = 22;   // título de sección
    private const F_META   = 14;   // metadatos del encabezado
    private const F_COLHDR = 15;   // encabezados de columna
    private const F_CELL   = 15;   // celdas de datos
    private const F_TOTAL  = 14;   // líneas de totales monetarios
    private const F_GRAND  = 18;   // "TOTAL" final
    private const F_SUB    = 11;   // subtítulos y timestamp

    // ── Anchos de columna base en px (× $fscale al renderizar) ───────────
    private const W_PROD   = 220;  // Producto (solo sección 1)
    private const W_COLOR  = 135;  // Color     (solo sección 1)
    private const W_TALLA  = 60;   // Cada columna de talla
    private const W_TOTAL  = 80;   // Columna Total

    // ── Altura fija del encabezado en px (× $fscale) ─────────────────────
    // MISMA en TODAS las secciones → garantiza alineación de filas.
    private const HEADER_H_BASE = 68;

    // ── Propiedades calculadas en __construct ─────────────────────────────
    private int   $paperPx;
    private float $fscale;
    private int   $margin;
    private int   $firstTallas;
    private int   $nextTallas;
    private bool  $debugImage;

    public function __construct()
    {
        $dpi              = max(150, (int) env('THERMAL_HORIZONTAL_DPI', 203));
        $paperMm          = max(40,  (int) env('THERMAL_HORIZONTAL_PAPER_MM', 72));
        $this->paperPx    = (int) round($paperMm * $dpi / 25.4);
        $this->fscale     = max(0.5, min(3.0, (float) env('THERMAL_HORIZONTAL_FONT_SCALE', 1.0)));
        $this->margin     = max(4,   (int) env('THERMAL_HORIZONTAL_MARGIN_PX', 8));
        $this->firstTallas = max(1,  (int) env('THERMAL_HORIZONTAL_FIRST_TALLAS', 4));
        $this->nextTallas  = max(1,  (int) env('THERMAL_HORIZONTAL_NEXT_TALLAS', 8));
        $this->debugImage  = (bool) env('THERMAL_HORIZONTAL_DEBUG_IMAGE', false);
    }

    // ── Punto de entrada ────────────────────────────────────────────────

    public function imprimir(int $recepcionId): array
    {
        $printerName = (string) env('THERMAL_PRINTER_NAME', 'POS-80');

        try {
            $payload = $this->construirPayload($recepcionId);
            $this->enviarRawWindows($printerName, $payload);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('EscposRecepcionHorizontalService: error inesperado', [
                'recepcion_id' => $recepcionId,
                'mensaje'      => $e->getMessage(),
                'trace'        => $e->getTraceAsString(),
            ]);
            throw ValidationException::withMessages([
                'printer' => 'Error al generar el ticket horizontal: ' . $e->getMessage(),
            ]);
        }

        return [
            'message'   => 'Ticket horizontal enviado a ' . $printerName . '.',
            'printer'   => $printerName,
            'recepcion' => $recepcionId,
        ];
    }

    // ── Construcción del payload ─────────────────────────────────────────

    private function construirPayload(int $recepcionId): string
    {
        // ── Consulta ──────────────────────────────────────────────────────
        $recepcion = RecepcionMercancia::query()
            ->with(['sucursal:scl_id,scl_nombre', 'almacen:alm_id,alm_nombre', 'proveedor:prv_id,prv_nombre_empresa'])
            ->findOrFail($recepcionId);

        $movimientos = MovimientoInventario::query()
            ->with([
                'sku:psk_id,psk_prd_id,psk_codigo,psk_nombre',
                'sku.producto:prd_id,prd_codigo,prd_nombre',
                'sku.valoresAtributo' => fn ($q) => $q
                    ->where('vat_deleted', false)->whereNull('vat_deleted_at')
                    ->where('vat_estatus', 'activo')
                    ->with(['atributo:atr_id,atr_nombre'])
                    ->orderBy('vat_valor'),
            ])
            ->where('min_rme_id', $recepcionId)
            ->where('min_deleted', false)->whereNull('min_deleted_at')
            ->where('min_estatus', 'activo')
            ->where('min_signo', '>', 0)
            ->orderBy('min_id')
            ->get();

        if ($movimientos->isEmpty()) {
            throw ValidationException::withMessages([
                'recepcion' => 'La recepcion no tiene movimientos definitivos para imprimir.',
            ]);
        }

        // ── Preparación ───────────────────────────────────────────────────
        [$filas, $todasTallas] = $this->prepararDatos($movimientos);
        $totales = $this->calcularTotales($recepcion, $movimientos);
        $meta    = [
            'folio'     => $this->ascii((string) ($recepcion->rme_folio ?? 'RME-' . $recepcionId)),
            'fecha'     => optional($recepcion->rme_fecha_captura)->format('d/m/Y H:i') ?? 'N/D',
            'sucursal'  => $this->ascii((string) ($recepcion->sucursal?->scl_nombre ?? 'N/D')),
            'almacen'   => $this->ascii((string) ($recepcion->almacen?->alm_nombre ?? 'N/D')),
            'proveedor' => $this->ascii((string) ($recepcion->proveedor?->prv_nombre_empresa ?? 'N/D')),
            'ref'       => trim($this->ascii((string) ($recepcion->rme_documento_referencia ?? ''))),
            'obs'       => trim($this->ascii((string) ($recepcion->rme_observaciones ?? ''))),
        ];

        // ── Plan de secciones ─────────────────────────────────────────────
        $sections    = $this->planificarSecciones($todasTallas);
        $nSections   = count($sections);
        $nFilas      = count($filas);

        // ── Alturas fijas compartidas (MISMAS en todas las secciones) ────
        $s        = $this->fscale;
        $headerH  = (int) round(self::HEADER_H_BASE * $s);
        $footerH  = $this->calcularFooterH($totales, $s);  // 0 si no hay dinero
        $rowH     = $this->calcularRowH($nFilas, $headerH, $footerH);

        // ── Render cada sección ───────────────────────────────────────────
        $payload = "\x1B\x40";  // ESC @ init

        foreach ($sections as $sec) {
            $canvas = $this->renderSeccion(
                meta:      $meta,
                filas:     $filas,
                totales:   $totales,
                seccion:   $sec,
                nSections: $nSections,
                headerH:   $headerH,
                footerH:   $footerH,
                rowH:      $rowH,
            );

            if ($this->debugImage) {
                $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR
                    . 'rme-hx-sec' . $sec['num'] . '-' . time() . '.png';
                \imagepng($canvas, $path);
                Log::debug('EscposRecepcionHorizontalService: debug PNG', ['path' => $path]);
            }

            $white   = \imagecolorallocate($canvas, 255, 255, 255);
            $rotated = \imagerotate($canvas, -90, $white);
            \imagedestroy($canvas);

            $payload .= "\x1B\x61\x00";         // LEFT
            $payload .= $this->toRaster($rotated);
            \imagedestroy($rotated);

            $payload .= "\n";
        }

        $payload .= "\n\n\n\n\x1D\x56\x00";    // avance + corte
        return $payload;
    }

    // ── Planificación de secciones ───────────────────────────────────────

    /**
     * Devuelve un array de secciones. Cada sección tiene:
     *   num           — número ordinal (1, 2, 3, …)
     *   tallas        — array de etiquetas de talla a mostrar
     *   showLabels    — bool: mostrar columnas Producto y Color
     *   isFirst, isLast
     */
    private function planificarSecciones(array $todasTallas): array
    {
        if (empty($todasTallas)) {
            // Producto sin tallas / SKU simple
            return [[
                'num'        => 1,
                'tallas'     => [],
                'showLabels' => true,
                'isFirst'    => true,
                'isLast'     => true,
            ]];
        }

        $sections  = [];
        $remaining = $todasTallas;

        // Sección 1 — con columnas Producto y Color
        $chunk     = array_splice($remaining, 0, $this->firstTallas);
        $sections[] = ['tallas' => $chunk, 'showLabels' => true];

        // Secciones 2+ — sin columnas Producto/Color
        while (!empty($remaining)) {
            $chunk     = array_splice($remaining, 0, $this->nextTallas);
            $sections[] = ['tallas' => $chunk, 'showLabels' => false];
        }

        $total = count($sections);
        foreach ($sections as $i => &$sec) {
            $sec['num']     = $i + 1;
            $sec['isFirst'] = $i === 0;
            $sec['isLast']  = $i === $total - 1;
        }
        unset($sec);

        return $sections;
    }

    // ── Cálculo de altura de fila (compartida en todas las secciones) ────

    /**
     * Calcula rowH para que el contenido llene paperPx en la sección más
     * restrictiva (la última, que tiene footer de totales).
     * La misma rowH se aplica a TODAS las secciones → alineación perfecta.
     */
    private function calcularRowH(int $nFilas, int $headerH, int $footerH): int
    {
        $override = (int) env('THERMAL_HORIZONTAL_ROW_HEIGHT', 0);
        if ($override > 0) {
            return max(20, $override);
        }

        // (nFilas + 1) renglones: 1 para encabezado de columnas + nFilas de datos
        $disponible = $this->paperPx - 2 * $this->margin - $headerH - $footerH;
        $divisor    = max(1, $nFilas + 1);
        return max(24, min(80, (int) floor($disponible / $divisor)));
    }

    /**
     * Altura estimada del bloque de totales (solo aparece en la última sección).
     */
    private function calcularFooterH(array $totales, float $s): int
    {
        $lh = $this->lh(self::F_TOTAL, $s);    // alto de línea normal
        $lg = $this->lh(self::F_GRAND, $s);    // alto de línea TOTAL

        $lines = 1;  // Artículos (siempre)
        if ($totales['subtotal']  > 0) $lines++;
        if ($totales['descuento'] > 0) $lines++;
        if ($totales['flete']     > 0) $lines++;
        if ($totales['iva']       > 0) $lines++;

        $h = 10                         // separador superior
            + $lines * $lh             // líneas normales
            + ($totales['total'] > 0 ? 6 + $lg : 0)  // sep + TOTAL
            + $this->lh(self::F_SUB, $s) + 6;         // timestamp

        return (int) $h;
    }

    // ── Renderizado de una sección ───────────────────────────────────────

    private function renderSeccion(
        array $meta,
        array $filas,
        array $totales,
        array $seccion,
        int   $nSections,
        int   $headerH,
        int   $footerH,
        int   $rowH,
    ) {
        $fontR = $this->fontPath(false);
        $fontB = $this->fontPath(true);
        $s     = $this->fscale;
        $m     = $this->margin;

        $showLabels  = $seccion['showLabels'];
        $isFirst     = $seccion['isFirst'];
        $isLast      = $seccion['isLast'];
        $secNum      = $seccion['num'];
        $stripTallas = $seccion['tallas'];
        $nTallas     = count($stripTallas);

        // ── Anchos escalados ──────────────────────────────────────────────
        $wProd  = (int) round(self::W_PROD  * $s);
        $wColor = (int) round(self::W_COLOR * $s);
        $wTalla = (int) round(self::W_TALLA * $s);
        $wTotal = (int) round(self::W_TOTAL * $s);

        // ── Canvas ────────────────────────────────────────────────────────
        $labelsW = $showLabels ? ($wProd + $wColor) : 0;
        $canvasW = 2 * $m + $labelsW + $nTallas * $wTalla + $wTotal;
        $canvasH = $this->paperPx;  // FIJO = ancho imprimible del papel

        $img   = \imagecreatetruecolor($canvasW, $canvasH);
        $white = \imagecolorallocate($img, 255, 255, 255);
        $black = \imagecolorallocate($img, 0, 0, 0);
        $gray  = \imagecolorallocate($img, 105, 105, 105);
        $lgray = \imagecolorallocate($img, 220, 220, 220);
        $bgalt = \imagecolorallocate($img, 246, 246, 246);
        \imagefilledrectangle($img, 0, 0, $canvasW, $canvasH, $white);

        // Helpers
        $t = function (string $txt, int $x, int $y, int $pt, bool $bold = false, int $col = -1)
            use ($img, $fontR, $fontB, $black, $s): void {
            \imagettftext($img, $pt * $s, 0, $x, $y, $col < 0 ? $black : $col, $bold ? $fontB : $fontR, $txt);
        };
        $tw = function (string $txt, int $pt, bool $bold = false)
            use ($fontR, $fontB, $s): int {
            $b = \imagettfbbox($pt * $s, 0, $bold ? $fontB : $fontR, $txt);
            return (int) abs($b[2] - $b[0]);
        };

        // ── ENCABEZADO (altura fija = $headerH, contenido distribuido) ────
        $y = $m;
        $this->dibujarEncabezado($img, $t, $tw, $fontR, $fontB, $meta, $totales, $seccion, $nSections, $s, $m, $canvasW, $y, $headerH, $black, $gray, $lgray);
        $y += $headerH;

        // ── FILA DE CABECERAS DE COLUMNA ──────────────────────────────────
        \imagefilledrectangle($img, $m, $y, $canvasW - $m, $y + $rowH, $lgray);
        \imagerectangle($img, $m, $y, $canvasW - $m, $y + $rowH, $black);

        $cx = $m;
        $fch = (int) round(self::F_COLHDR * $s);

        if ($showLabels) {
            $this->celda($img, $fontB, $fch, 'Producto', $cx, $y, $wProd, $rowH, 'L', $black);
            $cx += $wProd;
            \imageline($img, $cx, $y, $cx, $y + $rowH, $black);
            $this->celda($img, $fontB, $fch, 'Color', $cx, $y, $wColor, $rowH, 'L', $black);
            $cx += $wColor;
            \imageline($img, $cx, $y, $cx, $y + $rowH, $black);
        }

        foreach ($stripTallas as $talla) {
            $this->celda($img, $fontB, $fch, $this->trunc($talla, 8), $cx, $y, $wTalla, $rowH, 'C', $black);
            $cx += $wTalla;
            \imageline($img, $cx, $y, $cx, $y + $rowH, $black);
        }

        // En última sección: "Total" = total global. En otras: total parcial de este strip.
        $totalLabel = $isLast ? 'Total' : 'Parcial';
        $this->celda($img, $fontB, $fch, $totalLabel, $cx, $y, $wTotal, $rowH, 'R', $black);

        $y += $rowH;

        // ── FILAS DE DATOS ────────────────────────────────────────────────
        $prevProd = null;
        $rowIdx   = 0;
        $fce      = (int) round(self::F_CELL * $s);

        foreach ($filas as $fila) {
            // Separador visual entre productos distintos
            if ($prevProd !== null && $prevProd !== $fila['producto']) {
                \imageline($img, $m, $y, $canvasW - $m, $y, $gray);
            }
            $prevProd = $fila['producto'];

            // Fondo alternado
            if ($rowIdx % 2 !== 0) {
                \imagefilledrectangle($img, $m, $y + 1, $canvasW - $m, $y + $rowH - 1, $bgalt);
            }

            $cx = $m;

            // Columnas de identificación (solo sección 1)
            if ($showLabels) {
                $prodTxt = $fila['codigo'] !== ''
                    ? $this->trunc($fila['codigo'], 6) . ' ' . $this->trunc($fila['producto'], 22)
                    : $this->trunc($fila['producto'], 28);
                $this->celda($img, $fontB, $fce, $prodTxt, $cx, $y, $wProd, $rowH, 'L', $black);
                $cx += $wProd;
                \imageline($img, $cx, $y, $cx, $y + $rowH, $lgray);

                $this->celda($img, $fontR, $fce, $this->trunc($fila['color'], 16), $cx, $y, $wColor, $rowH, 'L', $black);
                $cx += $wColor;
                \imageline($img, $cx, $y, $cx, $y + $rowH, $lgray);
            }

            // Columnas de talla
            $parcial = 0.0;
            foreach ($stripTallas as $talla) {
                $qty  = (float) ($fila['cells'][$talla] ?? 0);
                $parcial += $qty;
                $cell = $qty > 0 ? number_format((int) $qty, 0) : '-';
                $col  = $qty > 0 ? $black : $gray;
                $this->celda($img, $qty > 0 ? $fontB : $fontR, $fce, $cell, $cx, $y, $wTalla, $rowH, 'C', $col);
                $cx += $wTalla;
                \imageline($img, $cx, $y, $cx, $y + $rowH, $lgray);
            }

            // Columna de total: grand total en última sección, parcial en otras
            $totalVal = $isLast
                ? number_format((int) $fila['total'], 0)
                : ($parcial > 0 ? number_format((int) $parcial, 0) : '-');
            $totalCol = ($isLast || $parcial > 0) ? $black : $gray;
            $this->celda($img, $fontB, $fce, $totalVal, $cx, $y, $wTotal, $rowH, 'R', $totalCol);

            $y += $rowH;
            $rowIdx++;
        }

        // Línea inferior de la tabla
        \imageline($img, $m, $y, $canvasW - $m, $y, $black);
        $y += 4;

        // ── BLOQUE DE TOTALES MONETARIOS (solo última sección) ────────────
        if ($isLast) {
            $this->dibujarTotales($img, $t, $tw, $totales, $m, $canvasW, $y, $s, $gray, $black);
        }

        return $img;
    }

    // ── Encabezado compacto distribuido por sección ──────────────────────

    /**
     * Dibuja el bloque de encabezado dentro del rectángulo [y, y+headerH].
     * El contenido VARÍA por sección pero la altura es siempre $headerH.
     */
    private function dibujarEncabezado(
        $img, callable $t, callable $tw,
        string $fontR, string $fontB,
        array $meta, array $totales,
        array $seccion, int $nSections,
        float $s, int $m, int $canvasW,
        int $y0, int $headerH,
        int $black, int $gray, int $lgray,
    ): void {
        $secNum  = $seccion['num'];
        $fTitle  = (int) round(self::F_TITLE * $s);
        $fMeta   = (int) round(self::F_META  * $s);
        $fSub    = (int) round(self::F_SUB   * $s);
        $lhMeta  = $this->lh(self::F_META, $s);
        $lhTitle = $this->lh(self::F_TITLE, $s);

        // Separador inferior del encabezado
        \imageline($img, $m, $y0 + $headerH - 2, $canvasW - $m, $y0 + $headerH - 2, $black);

        $y  = $y0 + 4;   // posición actual dentro del bloque

        switch ($secNum) {
            case 1:
                // Título grande
                $t('ENTRADA DE MERCANCIA', $m, $y + $lhTitle - 4, self::F_TITLE, true);
                if ($nSections > 1) {
                    $lbl = "1/$nSections";
                    $t($lbl, $canvasW - $m - $tw($lbl, self::F_SUB), $y + $lhTitle - 4, self::F_SUB, false, $gray);
                }
                $y += $lhTitle;

                // Línea compacta: No. Entrada | Proveedor
                $folio = 'No.' . $meta['folio'];
                $t($folio, $m, $y + $lhMeta - 3, self::F_META, true);
                $prvTrunc = $this->trunc($meta['proveedor'], 36);
                $tw_folio = $tw($folio, self::F_META, true);
                $t('  Prov: ' . $prvTrunc, $m + $tw_folio, $y + $lhMeta - 3, self::F_META, false, $gray);
                break;

            case 2:
                $lh = $lhMeta;
                $t('Fecha: ' . $meta['fecha'], $m, $y + $lh - 3, self::F_META, false);
                $y += $lh + 2;
                $t('Sucursal: ' . $this->trunc($meta['sucursal'], 28), $m, $y + $lh - 3, self::F_META, false);
                $t('Almacen: ' . $this->trunc($meta['almacen'], 22), (int) ($canvasW / 2), $y + $lh - 3, self::F_META, false);
                $y += $lh + 2;
                if ($nSections > 2) {
                    $lbl = "Sec. 2/$nSections";
                    $t($lbl, $canvasW - $m - $tw($lbl, self::F_SUB), $y + $lhMeta - 3, self::F_SUB, false, $gray);
                }
                break;

            case 3:
                $lh = $lhMeta;
                if ($meta['ref'] !== '') {
                    $t('Ref: ' . $this->trunc($meta['ref'], 50), $m, $y + $lh - 3, self::F_META, false);
                    $y += $lh + 2;
                }
                if ($meta['obs'] !== '') {
                    $t('Obs: ' . $this->trunc($meta['obs'], 50), $m, $y + $lh - 3, self::F_META, false, $gray);
                    $y += $lh + 2;
                }
                $artText = 'Total articulos: ' . number_format($totales['articulos'], 0) . ' pzas';
                $t($artText, $m, $y + $lh - 3, self::F_META, true);
                $lbl = "Sec. 3/$nSections";
                $t($lbl, $canvasW - $m - $tw($lbl, self::F_SUB), $y + $lh - 3, self::F_SUB, false, $gray);
                break;

            default:
                // Sección 4+: solo indicador compacto
                $lbl = "Sec. {$secNum}/{$nSections}";
                $t($lbl, $m, $y + $lhMeta - 3, self::F_META, false, $gray);
                break;
        }
    }

    // ── Bloque de totales monetarios ─────────────────────────────────────

    private function dibujarTotales(
        $img, callable $t, callable $tw,
        array $totales,
        int $m, int $canvasW, int $y,
        float $s, int $gray, int $black,
    ): void {
        $fTotal = (int) round(self::F_TOTAL * $s);
        $fGrand = (int) round(self::F_GRAND * $s);
        $fSub   = (int) round(self::F_SUB   * $s);
        $lhT    = $this->lh(self::F_TOTAL, $s);
        $lhG    = $this->lh(self::F_GRAND, $s);
        $lhS    = $this->lh(self::F_SUB,   $s);

        $y += 6;
        \imageline($img, $m, $y, $canvasW - $m, $y, $gray);
        $y += 6;

        $rows = [['Articulos:', number_format($totales['articulos'], 0) . ' pzas', false]];
        if ($totales['subtotal']  > 0) $rows[] = ['Subtotal:',  '$ ' . number_format($totales['subtotal'],  2, '.', ','), false];
        if ($totales['descuento'] > 0) $rows[] = ['Descuento:', '-$ ' . number_format($totales['descuento'], 2, '.', ','), false];
        if ($totales['flete']     > 0) $rows[] = ['Flete:',     '$ ' . number_format($totales['flete'],     2, '.', ','), false];
        if ($totales['iva']       > 0) $rows[] = ['IVA:',       '$ ' . number_format($totales['iva'],       2, '.', ','), false];

        foreach ($rows as [$lbl, $val, $bold]) {
            $bline = $y + $lhT - 3;
            $t($lbl, $m, $bline, self::F_TOTAL, false, $gray);
            $vw = $tw($val, self::F_TOTAL, false);
            $t($val, $canvasW - $m - $vw, $bline, self::F_TOTAL, false);
            $y += $lhT + 2;
        }

        if ($totales['total'] > 0) {
            \imageline($img, $m, $y, $canvasW - $m, $y, $black);
            $y += 4;
            $grandVal = '$ ' . number_format($totales['total'], 2, '.', ',');
            $t('TOTAL:', $m, $y + $lhG - 3, self::F_GRAND, true);
            $vw = $tw($grandVal, self::F_GRAND, true);
            $t($grandVal, $canvasW - $m - $vw, $y + $lhG - 3, self::F_GRAND, true);
            $y += $lhG + 4;
        }

        \imageline($img, $m, $y, $canvasW - $m, $y, $gray);
        $y += 4;

        $stamp = now()->format('d/m/Y H:i');
        $sw    = $tw($stamp, self::F_SUB, false);
        $t($stamp, $canvasW - $m - $sw, $y + $lhS - 3, self::F_SUB, false, $gray);
    }

    // ── Helper: texto alineado dentro de celda ───────────────────────────

    private function celda($img, string $font, int $ptScaled, string $text, int $x, int $y, int $w, int $h, string $align, int $color): void
    {
        $pad  = 4;
        $box  = \imagettfbbox($ptScaled, 0, $font, $text);
        $textW = (int) abs($box[2] - $box[0]);
        $bl   = $y + (int) ($h * 0.68);

        $drawX = match ($align) {
            'C' => $x + (int) (($w - $textW) / 2),
            'R' => $x + $w - $textW - $pad,
            default => $x + $pad,
        };

        \imagettftext($img, $ptScaled, 0, $drawX, $bl, $color, $font, $text);
    }

    // ── Helper: altura de línea para un tamaño de fuente ────────────────

    /** Devuelve el alto de línea en px para el tamaño de fuente dado (en escala). */
    private function lh(int $basePt, float $s): int
    {
        return (int) ceil($basePt * $s * 1.42) + 4;
    }

    // ── Preparación de datos ─────────────────────────────────────────────

    private function prepararDatos($movimientos): array
    {
        $tallaMap = [];
        $filaMap  = [];

        foreach ($movimientos as $mov) {
            $sku      = $mov->sku;
            $producto = $sku?->producto;
            if (!$sku || !$producto) {
                continue;
            }

            $color = 'GENERAL';
            $talla = $this->ascii((string) ($sku->psk_codigo ?? 'SKU'));

            foreach ($sku->valoresAtributo as $valor) {
                $n = (string) ($valor->atributo?->atr_nombre ?? '');
                $v = trim((string) ($valor->vat_valor ?? ''));
                if ($this->esColor($n)) {
                    $color = $v ?: 'GENERAL';
                } elseif ($this->esTalla($n)) {
                    $talla = $v !== '' ? $this->ascii($v) : $talla;
                }
            }

            $tallaMap[$talla] = true;

            $rowKey = $producto->prd_id . '|' . Str::lower(Str::ascii($color));
            if (!isset($filaMap[$rowKey])) {
                $filaMap[$rowKey] = [
                    'codigo'   => $this->ascii((string) ($producto->prd_codigo ?? '')),
                    'producto' => $this->ascii((string) ($producto->prd_nombre ?? $sku->psk_nombre ?? 'Producto')),
                    'color'    => $this->ascii($color),
                    'cells'    => [],
                    'total'    => 0.0,
                ];
            }

            $qty = (float) $mov->min_cantidad;
            $filaMap[$rowKey]['cells'][$talla] = ($filaMap[$rowKey]['cells'][$talla] ?? 0.0) + $qty;
            $filaMap[$rowKey]['total'] += $qty;
        }

        uksort($tallaMap, 'strnatcasecmp');
        uasort($filaMap, function (array $a, array $b): int {
            $c = strnatcasecmp($a['producto'], $b['producto']);
            return $c !== 0 ? $c : strnatcasecmp($a['color'], $b['color']);
        });

        return [array_values($filaMap), array_keys($tallaMap)];
    }

    // ── Totales monetarios ───────────────────────────────────────────────

    private function calcularTotales(RecepcionMercancia $recepcion, $movimientos): array
    {
        $subtotal  = round((float) $movimientos->sum('min_subtotal_linea'), 2);
        $descuento = round((float) $movimientos->sum('min_descuento_linea'), 2);
        $flete     = round((float) ($recepcion->rme_flete_total ?? 0), 2);
        $iva       = round((float) $movimientos->sum('min_iva_linea'), 2);
        $total     = round((float) $movimientos->sum('min_total_linea'), 2);
        $articulos = (float) $movimientos->sum('min_cantidad');

        if ($total <= 0 && ($subtotal > 0 || $flete > 0)) {
            $ivaPct  = round((float) ($recepcion->rme_iva_porcentaje ?? 0), 2);
            $tipoDoc = (string) ($recepcion->rme_documento_tipo ?? '');
            $base    = max(0.0, round($subtotal - $descuento + $flete, 2));
            $iva     = $tipoDoc === 'compra_factura' ? round($base * $ivaPct / 100, 2) : 0.0;
            $total   = round($base + $iva, 2);
        }

        return compact('articulos', 'subtotal', 'descuento', 'flete', 'iva', 'total');
    }

    // ── GD → ESC/POS raster (GS v 0) ────────────────────────────────────

    private function toRaster($image): string
    {
        $w  = \imagesx($image);
        $h  = \imagesy($image);
        $wb = (int) ceil($w / 8);
        $data = '';

        for ($y = 0; $y < $h; $y++) {
            for ($xb = 0; $xb < $wb; $xb++) {
                $byte = 0;
                for ($bit = 0; $bit < 8; $bit++) {
                    $x = $xb * 8 + $bit;
                    if ($x >= $w) {
                        continue;
                    }
                    $rgb  = \imagecolorat($image, $x, $y);
                    $luma = 0.299 * (($rgb >> 16) & 0xFF)
                          + 0.587 * (($rgb >> 8)  & 0xFF)
                          + 0.114 * ($rgb         & 0xFF);
                    if ($luma < 200) {
                        $byte |= (1 << (7 - $bit));
                    }
                }
                $data .= chr($byte);
            }
        }

        return "\x1D\x76\x30\x00"
            . chr($wb & 0xFF)       . chr(($wb >> 8) & 0xFF)
            . chr($h  & 0xFF)       . chr(($h  >> 8) & 0xFF)
            . $data;
    }

    // ── Fuentes TrueType ─────────────────────────────────────────────────

    private function fontPath(bool $bold = false): string
    {
        $paths = $bold
            ? ['C:\\Windows\\Fonts\\arialbd.ttf', 'C:\\Windows\\Fonts\\calibrib.ttf', 'C:\\Windows\\Fonts\\verdanab.ttf']
            : ['C:\\Windows\\Fonts\\arial.ttf',   'C:\\Windows\\Fonts\\calibri.ttf',  'C:\\Windows\\Fonts\\verdana.ttf'];

        foreach ($paths as $p) {
            if (is_file($p)) {
                return $p;
            }
        }

        throw ValidationException::withMessages([
            'printer' => 'No se encontro fuente TrueType de Windows para el ticket horizontal.',
        ]);
    }

    // ── Helpers de texto ─────────────────────────────────────────────────

    private function ascii(string $value): string
    {
        $clean = Str::ascii($value);
        return preg_replace('/[^\x20-\x7E]/', ' ', $clean) ?? '';
    }

    private function trunc(string $value, int $chars): string
    {
        $v = $this->ascii($value);
        return strlen($v) > $chars ? substr($v, 0, $chars) : $v;
    }

    private function esColor(string $n): bool
    {
        return str_contains(Str::lower(Str::ascii($n)), 'color');
    }

    private function esTalla(string $n): bool
    {
        $t = Str::lower(Str::ascii($n));
        return str_contains($t, 'talla') || str_contains($t, 'tamano') || str_contains($t, 'medida');
    }

    // ── Envío RAW a impresora Windows ────────────────────────────────────

    private function enviarRawWindows(string $printerName, string $payload): void
    {
        $dataPath   = tempnam(sys_get_temp_dir(), 'rme-hx-');
        $scriptBase = tempnam(sys_get_temp_dir(), 'rme-hxps-');

        if ($dataPath === false || $scriptBase === false) {
            throw ValidationException::withMessages([
                'printer' => 'No fue posible crear los archivos temporales para impresion.',
            ]);
        }

        $scriptPath = $scriptBase . '.ps1';
        file_put_contents($dataPath, $payload);
        file_put_contents($scriptPath, <<<'PS1'
param([string]$PrinterName,[string]$Path)
$signature = @"
using System;
using System.Runtime.InteropServices;
public class RawPrinterHelperHx {
  [StructLayout(LayoutKind.Sequential, CharSet=CharSet.Unicode)]
  public class DOCINFOA {
    [MarshalAs(UnmanagedType.LPWStr)] public string pDocName;
    [MarshalAs(UnmanagedType.LPWStr)] public string pOutputFile;
    [MarshalAs(UnmanagedType.LPWStr)] public string pDataType;
  }
  [DllImport("winspool.drv",EntryPoint="OpenPrinterW",SetLastError=true,CharSet=CharSet.Unicode)]
  public static extern bool OpenPrinter(string pPrinterName,out IntPtr phPrinter,IntPtr pDefault);
  [DllImport("winspool.drv",SetLastError=true)] public static extern bool ClosePrinter(IntPtr hPrinter);
  [DllImport("winspool.drv",SetLastError=true,CharSet=CharSet.Unicode)]
  public static extern bool StartDocPrinter(IntPtr hPrinter,Int32 Level,[In,MarshalAs(UnmanagedType.LPStruct)] DOCINFOA di);
  [DllImport("winspool.drv",SetLastError=true)] public static extern bool EndDocPrinter(IntPtr hPrinter);
  [DllImport("winspool.drv",SetLastError=true)] public static extern bool StartPagePrinter(IntPtr hPrinter);
  [DllImport("winspool.drv",SetLastError=true)] public static extern bool EndPagePrinter(IntPtr hPrinter);
  [DllImport("winspool.drv",SetLastError=true)]
  public static extern bool WritePrinter(IntPtr hPrinter,byte[] pBytes,Int32 dwCount,out Int32 dwWritten);
}
"@
Add-Type -TypeDefinition $signature -Language CSharp
$bytes    = [System.IO.File]::ReadAllBytes($Path)
$hPrinter = [IntPtr]::Zero
$di       = New-Object RawPrinterHelperHx+DOCINFOA
$di.pDocName  = "Recepcion horizontal"
$di.pDataType = "RAW"
if (-not [RawPrinterHelperHx]::OpenPrinter($PrinterName,[ref]$hPrinter,[IntPtr]::Zero)) { throw "No se pudo abrir la impresora '$PrinterName'." }
try {
  if (-not [RawPrinterHelperHx]::StartDocPrinter($hPrinter,1,$di)) { throw "No se pudo iniciar el documento." }
  try {
    if (-not [RawPrinterHelperHx]::StartPagePrinter($hPrinter)) { throw "No se pudo iniciar la pagina." }
    try {
      $written = 0
      if (-not [RawPrinterHelperHx]::WritePrinter($hPrinter,$bytes,$bytes.Length,[ref]$written)) { throw "No se pudieron enviar los datos." }
      if ($written -ne $bytes.Length) { throw "Bytes enviados: $written / $($bytes.Length)." }
    } finally { [void][RawPrinterHelperHx]::EndPagePrinter($hPrinter) }
  } finally { [void][RawPrinterHelperHx]::EndDocPrinter($hPrinter) }
} finally { [void][RawPrinterHelperHx]::ClosePrinter($hPrinter) }
PS1);

        $command = 'powershell -NoProfile -ExecutionPolicy Bypass -File '
            . escapeshellarg($scriptPath)
            . ' -PrinterName ' . escapeshellarg($printerName)
            . ' -Path '        . escapeshellarg($dataPath)
            . ' 2>&1';

        exec($command, $output, $exitCode);
        @unlink($dataPath);
        @unlink($scriptPath);
        @unlink($scriptBase);

        if ($exitCode !== 0) {
            $msg = trim(implode("\n", $output)) ?: 'No fue posible imprimir el ticket horizontal.';
            Log::error('EscposRecepcionHorizontalService: fallo al imprimir', [
                'printer'   => $printerName,
                'exit_code' => $exitCode,
                'output'    => $output,
            ]);
            throw ValidationException::withMessages(['printer' => $msg]);
        }
    }
}
