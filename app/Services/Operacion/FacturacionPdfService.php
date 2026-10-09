<?php

namespace App\Services\Operacion;

use App\Models\Facturacion;
use App\Models\PosTicketConfiguracion;
use App\Models\PosVenta;
use TCPDF;

/**
 * PDF carta con la representación impresa de un CFDI 4.0 SIMULADO. Solo lee datos persistidos: no emite,
 * no guarda y no toca ventas, caja ni inventario. UUID, sellos y certificados son simulados y el documento
 * se marca en cada página como sin valor fiscal; el QR no apunta al servicio de verificación del SAT.
 */
class FacturacionPdfService
{
    private const MARGEN = 14;

    private const TEXTO = '#1B1F23';

    private const SECUNDARIO = '#5A6472';

    private const LINEA = '#CDD3DA';

    private const FONDO = '#EEF1F5';

    private const MARCA = '#0F6CBD';

    public function __construct(private readonly FacturacionService $facturacion, private readonly CfdiSimuladoService $cfdi) {}

    public function nombreArchivo(Facturacion $factura): string
    {
        return 'factura-simulada-'.$factura->fac_folio.'.pdf';
    }

    public function generar(PosVenta $venta, Facturacion $factura): string
    {
        // Facturas emitidas antes de guardar la foto del documento (o del timbre) se arman con la misma regla, sin escribir nada.
        $doc = $factura->fac_documento ?: $this->facturacion->documento($venta, $factura);
        $cfdi = $doc['cfdi'] ?? $this->cfdi->generar($venta, $factura, $doc);
        $config = PosTicketConfiguracion::query()->first();
        $negocio = (string) config('app.business_name', 'La I. Suriana');

        $pdf = new class('P', 'mm', 'LETTER', true, 'UTF-8', false) extends TCPDF
        {
            public string $referencia = '';

            public string $pie = '';

            public function Header(): void
            {
                // Marca de agua en todas las páginas, debajo del contenido y legible en blanco y negro.
                $marca = 'SIMULADA · SIN VALOR FISCAL';
                $cx = $this->getPageWidth() / 2;
                $cy = $this->getPageHeight() / 2;
                $this->StartTransform();
                $this->Rotate(35, $cx, $cy);
                $this->SetFont('helvetica', 'B', 36);
                $this->SetTextColor(234, 236, 239);
                $this->Text($cx - $this->GetStringWidth($marca) / 2, $cy - 6, $marca);
                $this->StopTransform();
                // Desde la segunda página, una línea de referencia para no perder el contexto al imprimir.
                if ($this->getPage() < 2) {
                    return;
                }
                $this->SetXY($this->lMargin, 9);
                $this->SetFont('helvetica', '', 7.5);
                $this->SetTextColor(90, 100, 114);
                $this->Cell(0, 5, $this->referencia, 0, 1, 'L');
                $this->SetDrawColor(205, 211, 218);
                $this->Line($this->lMargin, 15, $this->getPageWidth() - $this->rMargin, 15);
            }

            public function Footer(): void
            {
                $this->SetY(-14);
                $this->SetDrawColor(205, 211, 218);
                $this->Line($this->lMargin, $this->GetY(), $this->getPageWidth() - $this->rMargin, $this->GetY());
                $this->Ln(1.5);
                $ancho = ($this->getPageWidth() - $this->lMargin - $this->rMargin) / 3;
                $this->SetFont('helvetica', 'B', 7.5);
                $this->SetTextColor(27, 31, 35);
                $this->Cell($ancho, 5, 'Documento de prueba. Sin valor fiscal.', 0, 0, 'L');
                $this->SetFont('helvetica', '', 7.5);
                $this->SetTextColor(90, 100, 114);
                $this->Cell($ancho, 5, $this->pie, 0, 0, 'C');
                $this->Cell($ancho, 5, $this->getAliasRightShift().'Página '.$this->getAliasNumPage().' de '.$this->getAliasNbPages(), 0, 0, 'R');
            }
        };

        $uuid = $cfdi['timbre']['uuid'] ?? '';
        $pdf->referencia = $negocio.' · Factura simulada '.$factura->fac_folio.' · UUID simulado '.$uuid.' (continuación)';
        $pdf->pie = 'CFDI simulado '.$factura->fac_folio;
        $pdf->SetCreator((string) config('app.name', $negocio));
        $pdf->SetAuthor($negocio);
        $pdf->SetTitle('Factura simulada '.$factura->fac_folio);
        $pdf->SetSubject('Representación impresa de un CFDI simulado, sin valor fiscal');
        $pdf->SetMargins(self::MARGEN, 20, self::MARGEN);
        $pdf->SetHeaderMargin(8);
        $pdf->SetFooterMargin(14);
        $pdf->SetAutoPageBreak(true, 19);
        $pdf->setImageScale(1.25);
        $pdf->setCellHeightRatio(1.2);
        $pdf->SetFont('helvetica', '', 8.5);
        $pdf->SetTextColor(27, 31, 35);
        $pdf->AddPage();

        $this->encabezado($pdf, $negocio, $config, $factura, $cfdi);
        $pdf->writeHTML($this->datos($doc, $cfdi), true, false, true, false, '');
        $pdf->Ln(1);
        $pdf->writeHTML($this->conceptos($cfdi['conceptos'] ?? []), true, false, true, false, '');
        $pdf->Ln(1);
        $pdf->writeHTML($this->totales($doc, $cfdi), true, false, true, false, '');
        if (filled($factura->fac_notas)) {
            $pdf->Ln(1);
            $pdf->writeHTML($this->observaciones((string) $factura->fac_notas), true, false, true, false, '');
        }
        $pdf->Ln(2);
        $this->timbre($pdf, $cfdi, (string) $factura->fac_folio);

        return $pdf->Output($this->nombreArchivo($factura), 'S');
    }

    private function encabezado(TCPDF $pdf, string $negocio, ?PosTicketConfiguracion $config, Facturacion $factura, array $cfdi): void
    {
        $ancho = $pdf->getPageWidth() - 2 * self::MARGEN;
        $izquierda = $ancho * 0.5;
        $y = 12;
        $yIzquierda = $y;
        $logo = $config?->ptc_logo_path ? storage_path('app/public/'.$config->ptc_logo_path) : null;
        if ($logo && is_file($logo) && $this->logoCompatible($logo)) {
            // El logo se ajusta a su caja sin deformarse.
            $pdf->Image($logo, self::MARGEN, $y, 46, 18, '', '', '', true, 300, '', false, false, 0, 'LT');
            $yIzquierda = $y + 19;
            $marca = '';
        } else {
            $marca = '<div style="font-size:15pt;font-weight:bold;">'.e($negocio).'</div>';
        }
        $emisor = $cfdi['emisor'] ?? [];
        $fila = fn (string $etiqueta, string $valor) => '<tr><td width="34%" style="color:'.self::SECUNDARIO.';">'.e($etiqueta).'</td><td width="66%">'.e($valor).'</td></tr>';
        $texto = trim((string) $config?->ptc_texto_encabezado);
        $html = $marca
            .'<div style="font-size:7.5pt;font-weight:bold;color:'.self::SECUNDARIO.';">Emisor</div>'
            .'<table cellpadding="0.8" cellspacing="0" style="font-size:7.5pt;">'
            .$fila('Razón social', $emisor['nombre'] ?? 'Sin configurar').$fila('RFC', $emisor['rfc'] ?? 'Sin configurar')
            .$fila('Régimen fiscal', $emisor['regimen'] ?? 'Sin configurar').$fila('Lugar de expedición', $emisor['cp'] ?? 'Sin configurar')
            .'</table>'
            .($texto !== '' ? '<div style="font-size:7.5pt;color:'.self::SECUNDARIO.';">'.nl2br(e($texto)).'</div>' : '');
        $pdf->writeHTMLCell($izquierda - 4, 0, self::MARGEN, $yIzquierda, $html, 0, 1, false, true, 'L');
        $yIzquierda = $pdf->GetY();

        $comprobante = $cfdi['comprobante'] ?? [];
        $dato = fn (string $etiqueta, string $valor, string $estilo = '') => '<tr><td width="42%" align="right" style="color:'.self::SECUNDARIO.';">'.e($etiqueta).'</td><td width="58%" align="right" style="'.$estilo.'">'.e($valor).'</td></tr>';
        $titulo = '<div style="font-size:16pt;font-weight:bold;color:'.self::MARCA.';">FACTURA SIMULADA</div>'
            .'<div style="font-size:7.5pt;color:'.self::SECUNDARIO.';">Representación impresa de un CFDI '.e($comprobante['version'] ?? '4.0').' simulado</div>'
            .'<table cellpadding="1" cellspacing="0" style="font-size:8pt;">'
            .$dato('Serie y folio', ($comprobante['serie'] ?? '').' '.($comprobante['folio'] ?? ''), 'font-weight:bold;')
            .$dato('Folio interno', (string) $factura->fac_folio, 'font-weight:bold;')
            .$dato('Folio fiscal (UUID)', ($cfdi['timbre']['uuid'] ?? '').' (simulado)', 'font-size:7pt;')
            .$dato('Fecha de emisión', (string) ($comprobante['fecha'] ?? ''))
            .$dato('No. certificado emisor', ($cfdi['timbre']['certificado_emisor'] ?? '').' (simulado)', 'font-size:7pt;')
            .'</table>'
            .'<table cellpadding="2.5" cellspacing="0"><tr><td align="center" style="border:0.3mm solid '.self::TEXTO.';font-size:8pt;font-weight:bold;">Documento de prueba · Sin valor fiscal</td></tr></table>';
        $pdf->writeHTMLCell($ancho - $izquierda, 0, self::MARGEN + $izquierda, $y, $titulo, 0, 1, false, true, 'R');

        $pdf->SetY(max($yIzquierda, $pdf->GetY()) + 2);
        $pdf->SetDrawColor(15, 108, 189);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(self::MARGEN, $pdf->GetY(), self::MARGEN + $ancho, $pdf->GetY());
        $pdf->SetLineWidth(0.2);
        $pdf->Ln(3);
    }

    private function datos(array $doc, array $cfdi): string
    {
        $etiqueta = fn (string $texto) => '<span style="font-size:7pt;color:'.self::SECUNDARIO.';">'.e($texto).'</span><br/>';
        $titulo = fn (string $texto) => '<div style="font-size:7.5pt;font-weight:bold;color:'.self::SECUNDARIO.';">'.e($texto).'</div>';
        $receptor = $cfdi['receptor'] ?? [];
        $cliente = $doc['cliente'] ?? [];
        $html = $titulo('Receptor').'<span style="font-size:9pt;font-weight:bold;">'.e($receptor['nombre'] ?? '').'</span>'
            .'<br/>'.$etiqueta('RFC').e($receptor['rfc'] ?? '')
            .'<br/>'.$etiqueta('Régimen fiscal').e($receptor['regimen'] ?? '')
            .'<br/>'.$etiqueta('Domicilio fiscal (C.P.)').e($receptor['cp'] ?? '')
            .'<br/>'.$etiqueta('Uso del CFDI').e($receptor['uso'] ?? '');
        // Datos de contacto registrados del cliente, cuando existen.
        if (($cliente['nombre'] ?? 'Público general') !== 'Público general' && mb_strtoupper($cliente['nombre']) !== ($receptor['nombre'] ?? '')) {
            $html .= '<br/>'.$etiqueta('Cliente').e($cliente['nombre']);
        }
        foreach (['domicilio' => 'Domicilio', 'telefono' => 'Teléfono', 'email' => 'Correo'] as $clave => $texto) {
            if (! empty($cliente[$clave])) {
                $html .= '<br/>'.$etiqueta($texto).e($cliente[$clave]);
            }
        }
        $c = $cfdi['comprobante'] ?? [];
        $comprobante = $titulo('Comprobante').$etiqueta('Tipo de comprobante').e($c['tipo'] ?? '')
            .'<br/>'.$etiqueta('Forma de pago').e($c['forma_pago'] ?? '')
            .'<br/>'.$etiqueta('Método de pago').e($c['metodo_pago'] ?? '')
            .'<br/>'.$etiqueta('Moneda').e($c['moneda'] ?? '')
            .'<br/>'.$etiqueta('Exportación').e($c['exportacion'] ?? '');
        $venta = $doc['venta'] ?? [];
        $referencia = $titulo('Referencia interna').$etiqueta('Venta original').'<b>'.e($venta['folio'] ?? '—').'</b>'
            .(! empty($venta['fecha']) ? ' · '.e($venta['fecha']) : '')
            .(! empty($venta['sucursal']) ? '<br/>'.$etiqueta('Sucursal').e($venta['sucursal']) : '')
            .'<br/>'.$etiqueta('Almacén para facturar').'<b>'.e($doc['almacen'] ?? '—').'</b>'
            .'<br/>'.$etiqueta('Origen de las partidas').e(($doc['origen'] ?? '') === 'directa' ? 'Venta facturada directamente' : 'Ticket ajustado guardado');

        $celda = 'style="border:0.2mm solid '.self::LINEA.';font-size:8pt;line-height:1.25;"';

        return '<table cellpadding="5" cellspacing="0" width="100%"><tr>'
            .'<td width="38%" '.$celda.'>'.$html.'</td>'
            .'<td width="31%" '.$celda.'>'.$comprobante.'</td>'
            .'<td width="31%" '.$celda.'>'.$referencia.'</td>'
            .'</tr></table>';
    }

    private function conceptos(array $conceptos): string
    {
        $conDescuento = collect($conceptos)->contains(fn ($c) => (float) $c['descuento'] > 0);
        $columnas = $conDescuento
            ? [['Clave prod./serv.', '10%', 'left'], ['No. identificación', '13%', 'left'], ['Cantidad', '8%', 'right'], ['Unidad', '9%', 'left'], ['Descripción', '27%', 'left'], ['Valor unitario', '11%', 'right'], ['Descuento', '10%', 'right'], ['Importe', '12%', 'right']]
            : [['Clave prod./serv.', '10%', 'left'], ['No. identificación', '14%', 'left'], ['Cantidad', '8%', 'right'], ['Unidad', '9%', 'left'], ['Descripción', '35%', 'left'], ['Valor unitario', '12%', 'right'], ['Importe', '12%', 'right']];
        $html = '<table cellpadding="3" cellspacing="0" width="100%"><thead><tr>';
        foreach ($columnas as [$texto, $ancho, $align]) {
            $html .= '<th width="'.$ancho.'" align="'.$align.'" style="font-weight:bold;font-size:7pt;background-color:'.self::FONDO.';border-top:0.3mm solid '.self::TEXTO.';border-bottom:0.3mm solid '.self::TEXTO.';">'.$texto.'</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($conceptos as $c) {
            $estilo = 'style="border-bottom:0.2mm solid '.self::LINEA.';font-size:7.5pt;"';
            $impuesto = '<br/><span style="font-size:6.5pt;color:'.self::SECUNDARIO.';">Objeto de impuesto: '.e($c['objeto_impuesto']).' · Traslado IVA 002 Tasa '.number_format((float) config('facturacion.iva', 0.16), 6).' · Base '.$this->dinero($c['base']).' · Importe '.$this->dinero($c['iva']).'</span>';
            $celdas = [
                e($c['clave_prod_serv']), e($c['no_identificacion']), $this->cantidad($c['cantidad']),
                e($c['clave_unidad']).'<br/><span style="font-size:6.5pt;color:'.self::SECUNDARIO.';">'.e($c['unidad']).'</span>',
                e($c['descripcion']).$impuesto, $this->dinero($c['valor_unitario']),
            ];
            if ($conDescuento) {
                $celdas[] = (float) $c['descuento'] > 0 ? '-'.$this->dinero($c['descuento']) : $this->dinero(0);
            }
            $celdas[] = $this->dinero($c['importe']);
            // nobr evita que un concepto quede partido entre dos páginas.
            $html .= '<tr nobr="true">';
            foreach ($celdas as $i => $valor) {
                $html .= '<td width="'.$columnas[$i][1].'" align="'.$columnas[$i][2].'" '.$estilo.'>'.$valor.'</td>';
            }
            $html .= '</tr>';
        }

        return $html.'</tbody></table>';
    }

    private function totales(array $doc, array $cfdi): string
    {
        $c = $cfdi['comprobante'] ?? [];
        $importes = $doc['importes'] ?? [];
        $filas = [['Subtotal', $this->dinero($c['subtotal'] ?? 0)]];
        if ((float) ($c['descuento'] ?? 0) > 0) {
            $filas[] = ['Descuento', '-'.$this->dinero($c['descuento'])];
        }
        $filas[] = ['IVA trasladado '.rtrim(rtrim(number_format(($c['tasa'] ?? 0.16) * 100, 2), '0'), '.').'%', $this->dinero($c['iva'] ?? 0)];
        $detalle = '';
        foreach ($filas as [$texto, $valor]) {
            $detalle .= '<tr><td width="58%" style="color:'.self::SECUNDARIO.';">'.e($texto).'</td><td width="42%" align="right">'.e($valor).'</td></tr>';
        }
        $total = 'font-size:12pt;font-weight:bold;background-color:'.self::FONDO.';border-top:0.4mm solid '.self::TEXTO.';';
        $detalle .= '<tr><td width="58%" style="'.$total.'">Total</td><td width="42%" align="right" style="'.$total.'">'.e($this->dinero($c['total'] ?? 0)).'</td></tr>';

        $numero = count($cfdi['conceptos'] ?? []);
        $notas = [$numero.' '.($numero === 1 ? 'concepto' : 'conceptos').'. Importes en pesos mexicanos.',
            'IVA desglosado a partir de precios con IVA incluido (simulación).'];
        $extras = array_filter([
            (float) ($importes['descuento_partidas'] ?? 0) > 0 ? 'descuentos en partidas '.$this->dinero($importes['descuento_partidas']) : null,
            (float) ($importes['descuento_global'] ?? 0) > 0 ? 'descuento general '.$this->dinero($importes['descuento_global']) : null,
            (float) ($importes['credito_cambio'] ?? 0) > 0 ? 'crédito de cambio '.$this->dinero($importes['credito_cambio']) : null,
        ]);
        if ($extras) {
            $notas[] = 'El descuento corresponde a lo registrado en la venta (con IVA): '.implode(', ', $extras).'.';
        }
        $izquierda = '<span style="font-size:7pt;color:'.self::SECUNDARIO.';">Total con letra</span><br/>'
            .'<span style="font-size:8.5pt;font-weight:bold;">'.e($c['total_letra'] ?? '').'</span><br/>'
            .'<span style="font-size:7pt;color:'.self::SECUNDARIO.';">'.e(implode(' ', $notas)).'</span>';

        // Todo el bloque viaja junto: nunca queda el total separado de su desglose.
        return '<table cellpadding="0" cellspacing="0" width="100%" nobr="true"><tr>'
            .'<td width="55%">'.$izquierda.'</td><td width="3%"></td>'
            .'<td width="42%"><table cellpadding="3" cellspacing="0" width="100%" style="font-size:8.5pt;">'.$detalle.'</table></td>'
            .'</tr></table>';
    }

    private function observaciones(string $notas): string
    {
        return '<table cellpadding="5" cellspacing="0" width="100%" nobr="true"><tr><td style="border:0.2mm solid '.self::LINEA.';font-size:8pt;line-height:1.3;">'
            .'<span style="font-size:7.5pt;font-weight:bold;color:'.self::SECUNDARIO.';">Observaciones</span><br/>'.nl2br(e($notas)).'</td></tr></table>';
    }

    /** Timbre simulado: QR a la izquierda y sellos a la derecha; el bloque se mide para no partirlo entre páginas. */
    private function timbre(TCPDF $pdf, array $cfdi, string $folio): void
    {
        $t = $cfdi['timbre'] ?? [];
        $ancho = $pdf->getPageWidth() - 2 * self::MARGEN;
        $qr = 32;
        $mono = fn (string $etiqueta, string $valor) => '<tr><td style="font-size:6.5pt;font-weight:bold;color:'.self::SECUNDARIO.';">'.e($etiqueta).'</td></tr>'
            .'<tr><td style="font-family:courier;font-size:6pt;line-height:1.15;">'.e($valor).'</td></tr>';
        $dato = fn (string $etiqueta, string $valor, string $ancho) => '<td width="'.$ancho.'"><span style="font-size:6.5pt;color:'.self::SECUNDARIO.';">'.e($etiqueta).'</span><br/><span style="font-size:7.5pt;">'.e($valor).'</span></td>';
        $html = '<div style="font-size:8pt;font-weight:bold;">Timbre fiscal digital (simulado, sin certificación de un PAC ni del SAT)</div>'
            .'<table cellpadding="1" cellspacing="0"><tr>'
            .$dato('Folio fiscal (UUID)', $t['uuid'] ?? '', '37%').$dato('Fecha de certificación', $t['fecha_timbrado'] ?? '', '21%')
            .$dato('No. certificado del SAT', $t['certificado_sat'] ?? '', '24%').$dato('RFC proveedor de certificación', $t['rfc_proveedor'] ?? '', '18%')
            .'</tr></table>'
            .'<table cellpadding="0.6" cellspacing="0">'
            .$mono('Sello digital del CFDI (simulado)', $t['sello_cfd'] ?? '')
            .$mono('Sello del SAT (simulado)', $t['sello_sat'] ?? '')
            .$mono('Cadena original del complemento de certificación digital (simulada)', $t['cadena_original'] ?? '')
            .'</table>'
            .'<div style="font-size:7pt;font-weight:bold;">Este documento es una representación impresa de un CFDI SIMULADO. No es un comprobante fiscal, no fue certificado y no puede verificarse ante el SAT.</div>';
        $anchoTexto = $ancho - $qr - 4;

        // Se mide el bloque en una transacción y, si no cabe, pasa completo a la página siguiente.
        $pdf->startTransaction();
        $inicio = $pdf->GetY();
        $pagina = $pdf->getPage();
        $pdf->writeHTMLCell($anchoTexto, 0, self::MARGEN + $qr + 4, $inicio, $html, 0, 1, false, true, 'L');
        $alto = $pdf->getPage() === $pagina ? $pdf->GetY() - $inicio : PHP_INT_MAX;
        $pdf->rollbackTransaction(true);
        if ($alto === PHP_INT_MAX || $inicio + max($alto, $qr + 8) > $pdf->getPageHeight() - 19) {
            $pdf->AddPage();
        }
        $y = $pdf->GetY();
        $pdf->SetDrawColor(205, 211, 218);
        $pdf->Line(self::MARGEN, $y, self::MARGEN + $ancho, $y);
        $y += 2;
        $pdf->write2DBarcode($this->cfdi->textoQr($cfdi, $folio), 'QRCODE,M', self::MARGEN, $y, $qr, $qr, ['border' => false, 'padding' => 0, 'fgcolor' => [0, 0, 0], 'bgcolor' => false], 'N');
        $pdf->SetFont('helvetica', '', 6);
        $pdf->SetTextColor(90, 100, 114);
        $pdf->SetXY(self::MARGEN, $y + $qr + 0.5);
        $pdf->MultiCell($qr, 3, "QR simulado:\nno verificable ante el SAT", 0, 'C');
        $pdf->SetTextColor(27, 31, 35);
        $pdf->writeHTMLCell($anchoTexto, 0, self::MARGEN + $qr + 4, $y, $html, 0, 1, false, true, 'L');
    }

    private function dinero(mixed $valor): string
    {
        return '$'.number_format((float) $valor, 2, '.', ',');
    }

    private function cantidad(mixed $valor): string
    {
        $numero = round((float) $valor, 2);

        return floor($numero) === $numero ? number_format($numero, 0, '.', ',') : number_format($numero, 2, '.', ',');
    }

    /** Igual que el ticket de Punto de venta: un PNG con transparencia requiere GD o Imagick en TCPDF. */
    private function logoCompatible(string $ruta): bool
    {
        $cabecera = @file_get_contents($ruta, false, null, 0, 26);
        $pngConAlfa = is_string($cabecera) && str_starts_with($cabecera, "\x89PNG") && in_array(ord($cabecera[25] ?? "\0"), [4, 6], true);

        return ! $pngConAlfa || extension_loaded('gd') || extension_loaded('imagick');
    }
}
