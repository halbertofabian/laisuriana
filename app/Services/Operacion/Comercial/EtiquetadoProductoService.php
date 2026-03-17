<?php

namespace App\Services\Operacion\Comercial;

use App\Models\ProductoSku;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use TCPDF;

class EtiquetadoProductoService
{
    public function __construct(private readonly AuditoriaService $auditoriaService)
    {
    }

    public function generarEtiquetaSku(Request $request, ProductoSku $sku, array $opciones = []): string
    {
        $formato = (string) Arr::get($opciones, 'formato', config('etiquetado.formato_default', 'zebra_50x30'));
        $copias = (int) Arr::get($opciones, 'copias', 1);
        $layout = $this->resolverLayout($formato, $opciones);

        if (!is_array($layout)) {
            throw new \InvalidArgumentException('Formato de etiqueta no configurado.');
        }

        $width = (float) Arr::get($layout, 'width_mm', 50.0);
        $height = (float) Arr::get($layout, 'height_mm', 30.0);
        $orientation = $width >= $height ? 'L' : 'P';

        $pdf = new TCPDF($orientation, 'mm', [$width, $height], true, 'UTF-8', false, false);
        $pdf->SetCreator(config('app.name', 'La Suriana Retail'));
        $pdf->SetAuthor(config('app.name', 'La Suriana Retail'));
        $pdf->SetTitle('Etiqueta ' . (string) $sku->psk_codigo);
        $pdf->SetSubject('Etiquetado de producto');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->SetCompression(false);
        $pdf->SetTextColor(0, 0, 0);

        $barcodeStyle = [
            'position' => '',
            'align' => 'C',
            'stretch' => false,
            'fitwidth' => true,
            'cellfitalign' => '',
            'border' => false,
            'hpadding' => 'auto',
            'vpadding' => 0,
            'fgcolor' => [0, 0, 0],
            'bgcolor' => false,
            'text' => false,
            'font' => 'helvetica',
            'fontsize' => 7,
            'stretchtext' => 4,
        ];

        $skuCodigo = $this->sanitizarCodigoSku((string) $sku->psk_codigo);
        $nombre = $this->resolverNombreProducto($sku);
        $precio = '$' . number_format((float) $sku->psk_precio, 2, '.', ',');

        $marginLeft = (float) Arr::get($layout, 'margin_left_mm', 2.0);
        $marginRight = (float) Arr::get($layout, 'margin_right_mm', 2.0);
        $marginTop = (float) Arr::get($layout, 'margin_top_mm', 1.8);
        $marginBottom = (float) Arr::get($layout, 'margin_bottom_mm', 1.8);
        $usableWidth = max(5.0, $width - $marginLeft - $marginRight);
        $usableBottom = $height - $marginBottom;

        for ($i = 0; $i < $copias; $i++) {
            $pdf->AddPage();

            $y = $marginTop;
            $barcodeHeight = (float) Arr::get($layout, 'barcode_height_mm', 9.5);

            $pdf->write1DBarcode(
                $skuCodigo,
                'C128',
                $marginLeft,
                $y,
                $usableWidth,
                $barcodeHeight,
                (float) Arr::get($layout, 'barcode_xres', 0.33),
                $barcodeStyle,
                'N'
            );

            $y += $barcodeHeight + (float) Arr::get($layout, 'gap_after_barcode_mm', 0.8);
            $pdf->SetFont('helvetica', '', (float) Arr::get($layout, 'font_sku_size', 7.5));
            $pdf->SetXY($marginLeft, $y);
            $pdf->Cell($usableWidth, (float) Arr::get($layout, 'line_height_mm', 3.0), $skuCodigo, 0, 1, 'C');

            $y = $pdf->GetY() + (float) Arr::get($layout, 'gap_after_sku_mm', 0.7);
            $pdf->SetFont('helvetica', 'B', (float) Arr::get($layout, 'font_nombre_size', 8.5));
            $pdf->SetXY($marginLeft, $y);
            $pdf->MultiCell(
                $usableWidth,
                (float) Arr::get($layout, 'line_height_mm', 3.0),
                $nombre,
                0,
                'L',
                false,
                1,
                '',
                '',
                true,
                0,
                false,
                true,
                (float) Arr::get($layout, 'nombre_max_height_mm', 9.0),
                'M'
            );

            $priceY = max($pdf->GetY(), $usableBottom - 4.3);
            $pdf->SetFont('helvetica', 'B', (float) Arr::get($layout, 'font_precio_size', 12.5));
            $pdf->SetXY($marginLeft, min($priceY, $usableBottom));
            $pdf->Cell($usableWidth, (float) Arr::get($layout, 'line_height_mm', 3.0), $precio, 0, 0, 'R');
        }

        $this->auditoriaService->registrarAccion(
            $request,
            'catalogo_comercial.sku.generar_etiqueta',
            'tbl_producto_skus_psk',
            (string) $sku->psk_id,
            [
                'psk_codigo' => $sku->psk_codigo,
                'formato' => $formato,
                'copias' => $copias,
            ]
        );

        return $pdf->Output('', 'S');
    }

    private function resolverNombreProducto(ProductoSku $sku): string
    {
        $tipoProducto = (string) ($sku->producto?->prd_tipo ?? 'simple');

        if ($tipoProducto === 'variable') {
            $nombre = trim((string) $sku->psk_nombre);
            $nombre = $nombre !== '' ? $nombre : 'SKU sin nombre';

            return Str::limit($nombre, 80, '');
        }

        $nombre = trim((string) ($sku->producto?->prd_nombre ?: $sku->psk_nombre ?: 'Producto sin nombre'));

        return Str::limit($nombre, 80, '');
    }

    private function sanitizarCodigoSku(string $codigo): string
    {
        $limpio = trim(Str::upper(Str::ascii($codigo)));

        if ($limpio === '') {
            return 'SKU-SIN-CODIGO';
        }

        return Str::replaceMatches('/[^A-Z0-9\\-\\.\\_\\/]/', '-', $limpio);
    }

    private function resolverLayout(string $formato, array $opciones): ?array
    {
        $layout = config("etiquetado.formatos.{$formato}");

        if (!is_array($layout)) {
            return null;
        }

        if (!filter_var(Arr::get($opciones, 'usar_configuracion_manual', false), FILTER_VALIDATE_BOOLEAN)) {
            return $layout;
        }

        foreach ([
            'width_mm',
            'height_mm',
            'margin_left_mm',
            'margin_right_mm',
            'margin_top_mm',
            'margin_bottom_mm',
            'barcode_height_mm',
            'barcode_xres',
        ] as $campo) {
            $valor = Arr::get($opciones, $campo);
            if ($valor !== null && $valor !== '') {
                $layout[$campo] = (float) $valor;
            }
        }

        return $layout;
    }
}
