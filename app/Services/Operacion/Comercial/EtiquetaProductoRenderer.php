<?php

namespace App\Services\Operacion\Comercial;

use Illuminate\Support\Str;
use TCPDF;

class EtiquetaProductoRenderer
{
    /**
     * Dibuja la composición clásica de producto dentro de una página o celda.
     */
    public function dibujar(
        TCPDF $pdf,
        array $datos,
        array $campos,
        float $ancho,
        float $alto,
        float $offsetX = 0,
        float $offsetY = 0,
        array $margenes = [],
    ): void {
        $escala = max(0.65, min(1.6, min($ancho / 50, $alto / 30)));
        $margenIzquierdo = max((float) ($margenes['izq'] ?? 0), 2 * $escala);
        $margenDerecho = max((float) ($margenes['der'] ?? 0), 2 * $escala);
        $margenSuperior = max((float) ($margenes['sup'] ?? 0), 1.8 * $escala);
        $margenInferior = max((float) ($margenes['inf'] ?? 0), 1.8 * $escala);
        $anchoUtil = max(5, $ancho - $margenIzquierdo - $margenDerecho);
        $limiteInferior = $offsetY + $alto - $margenInferior;
        $x = $offsetX + $margenIzquierdo;
        $y = $offsetY + $margenSuperior;

        $mostrar = static fn (string $campo): bool => in_array($campo, $campos, true);
        $sku = $this->sanitizarCodigo((string) ($datos['sku'] ?? ''));
        $codigoBarras = $this->sanitizarCodigo((string) (($datos['codigo_barras'] ?? '') ?: $sku));

        if ($mostrar('codigo_barras') && $codigoBarras !== '') {
            $altoBarcode = 9.5 * $escala;
            $pdf->write1DBarcode(
                $codigoBarras,
                'C128',
                $x,
                $y,
                $anchoUtil,
                $altoBarcode,
                0.33,
                $this->estiloBarcode(),
                'N',
            );
            $y += $altoBarcode + (0.8 * $escala);
        }

        if ($mostrar('sku') && $sku !== '') {
            $pdf->SetFont('helvetica', '', 7.5 * $escala);
            $pdf->SetXY($x, $y);
            $pdf->Cell($anchoUtil, 3 * $escala, $sku, 0, 1, 'C');
            $y = $pdf->GetY() + (0.7 * $escala);
        }

        if ($mostrar('nombre_producto') && !empty($datos['nombre_producto'])) {
            $pdf->SetFont('helvetica', 'B', 8.5 * $escala);
            $pdf->SetXY($x, $y);
            $pdf->MultiCell(
                $anchoUtil,
                3 * $escala,
                Str::limit((string) $datos['nombre_producto'], 80, ''),
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
                9 * $escala,
                'M',
            );
            $y = $pdf->GetY();
        }

        $secundarios = collect(['linea', 'talla', 'color', 'unidad', 'cantidad', 'sucursal', 'fecha_recepcion', 'folio_recepcion'])
            ->filter(fn (string $campo) => $mostrar($campo) && !empty($datos[$campo]))
            ->map(fn (string $campo) => (string) $datos[$campo])
            ->values()
            ->all();
        if ($secundarios !== []) {
            $pdf->SetFont('helvetica', '', 6.5 * $escala);
            $pdf->SetXY($x, $y + (0.4 * $escala));
            $pdf->MultiCell($anchoUtil, 2.6 * $escala, implode(' / ', $secundarios), 0, 'L');
        }

        if ($mostrar('precio') && !empty($datos['precio'])) {
            $precioY = max($pdf->GetY(), $limiteInferior - (4.3 * $escala));
            $pdf->SetFont('helvetica', 'B', 12.5 * $escala);
            $pdf->SetXY($x, min($precioY, $limiteInferior));
            $pdf->Cell($anchoUtil, 3 * $escala, (string) $datos['precio'], 0, 0, 'R');
        }
    }

    private function sanitizarCodigo(string $codigo): string
    {
        $limpio = trim(Str::upper(Str::ascii($codigo)));

        return Str::replaceMatches('/[^A-Z0-9\-\.\_\/]/', '-', $limpio);
    }

    private function estiloBarcode(): array
    {
        return [
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
    }
}
