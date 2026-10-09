<?php

namespace App\Services\Operacion;

use App\Models\Facturacion;
use App\Models\PosVenta;
use App\Models\ProductoSku;

/**
 * Arma los datos de una representación de CFDI 4.0 SIMULADO: comprobante, conceptos con IVA, UUID,
 * sellos, certificados y cadenas originales. Todo se genera localmente y de forma determinista
 * (mismo folio, mismos valores); no hay firma real, PAC ni SAT, y nada es verificable fiscalmente.
 */
class CfdiSimuladoService
{
    private const REGIMENES = [
        '601' => 'General de Ley Personas Morales', '603' => 'Personas Morales con Fines no Lucrativos',
        '605' => 'Sueldos y Salarios', '606' => 'Arrendamiento', '612' => 'Personas Físicas con Actividades Empresariales y Profesionales',
        '616' => 'Sin obligaciones fiscales', '621' => 'Incorporación Fiscal', '626' => 'Régimen Simplificado de Confianza',
    ];

    private const FORMAS_PAGO = [
        'efectivo' => ['01', 'Efectivo'], 'tarjeta' => ['04', 'Tarjeta de crédito'],
        'monedero_electronico' => ['05', 'Monedero electrónico'],
    ];

    private const UNIDADES = ['PZA' => ['H87', 'Pieza'], 'M' => ['MTR', 'Metro'], 'PAR' => ['PR', 'Par'], 'CJ' => ['XBX', 'Caja']];

    public const RFC_PUBLICO_GENERAL = 'XAXX010101000';

    public function generar(PosVenta $venta, Facturacion $factura, array $documento): array
    {
        $tasa = (float) config('facturacion.iva', 0.16);
        $emisor = $this->emisor();
        $receptor = $this->receptor($venta, $emisor['cp']);
        $conceptos = $this->conceptos($factura->fac_partidas ?? [], $documento, $tasa, (float) $factura->fac_total);
        $subtotal = round(array_sum(array_column($conceptos, 'importe')), 2);
        $descuento = round(array_sum(array_column($conceptos, 'descuento')), 2);
        $iva = round(array_sum(array_column($conceptos, 'iva')), 2);
        [$formaClave, $formaTexto] = self::FORMAS_PAGO[$venta->psv_metodo_pago] ?? ['99', 'Por definir'];
        $folio = (string) (int) preg_replace('/\D/', '', (string) $factura->fac_folio);
        $fecha = ($factura->fac_emitida_at ?? now())->copy();

        $comprobante = [
            'version' => '4.0', 'serie' => (string) config('facturacion.serie', 'SIM'), 'folio' => $folio,
            'fecha' => $fecha->format('Y-m-d\TH:i:s'), 'tipo' => 'I - Ingreso', 'exportacion' => '01 - No aplica',
            'moneda' => 'MXN - Peso mexicano', 'forma_pago' => $formaClave.' - '.$formaTexto,
            'metodo_pago' => 'PUE - Pago en una sola exhibición', 'lugar_expedicion' => $emisor['cp'],
            'subtotal' => $subtotal, 'descuento' => $descuento, 'iva' => $iva, 'tasa' => $tasa,
            'total' => round($subtotal - $descuento + $iva, 2),
            'total_letra' => $this->totalConLetra(round($subtotal - $descuento + $iva, 2)),
        ];

        $semilla = 'facturacion-simulada|'.$factura->fac_id.'|'.$factura->fac_folio.'|'.$fecha->format('c');
        $certificadoEmisor = $this->digitos($semilla.'|cert-emisor', 20);
        $certificadoSat = $this->digitos($semilla.'|cert-sat', 20);
        $uuid = $this->uuid($semilla);
        $cadena = $this->cadenaOriginal($comprobante, $emisor, $receptor, $conceptos, $certificadoEmisor);
        $selloCfd = $this->sello($cadena);
        $fechaTimbrado = $fecha->copy()->addSeconds(2)->format('Y-m-d\TH:i:s');
        $rfcProveedor = 'SIMULADO';
        $cadenaTimbre = '||1.1|'.$uuid.'|'.$fechaTimbrado.'|'.$rfcProveedor.'|'.$selloCfd.'|'.$certificadoSat.'||';

        return [
            'emisor' => $emisor, 'receptor' => $receptor, 'comprobante' => $comprobante, 'conceptos' => $conceptos,
            'timbre' => [
                'uuid' => $uuid, 'fecha_timbrado' => $fechaTimbrado, 'rfc_proveedor' => $rfcProveedor,
                'certificado_emisor' => $certificadoEmisor, 'certificado_sat' => $certificadoSat,
                'cadena_original' => $cadenaTimbre, 'sello_cfd' => $selloCfd, 'sello_sat' => $this->sello($cadenaTimbre.'|sat'),
            ],
        ];
    }

    /** Texto del QR: deliberadamente NO es la URL de verificación del SAT, para que nunca parezca verificable. */
    public function textoQr(array $cfdi, string $folioInterno): string
    {
        return implode("\n", [
            'FACTURA SIMULADA - SIN VALOR FISCAL', 'No verificable ante el SAT',
            'Folio: '.$folioInterno, 'UUID simulado: '.($cfdi['timbre']['uuid'] ?? ''),
            'Emisor: '.($cfdi['emisor']['rfc'] ?? ''), 'Receptor: '.($cfdi['receptor']['rfc'] ?? ''),
            'Total: '.number_format((float) ($cfdi['comprobante']['total'] ?? 0), 2, '.', ''),
        ]);
    }

    public function regimen(?string $clave): string
    {
        return $clave ? $clave.' - '.(self::REGIMENES[$clave] ?? 'Régimen') : 'Sin configurar';
    }

    private function emisor(): array
    {
        $config = config('facturacion.emisor', []);

        return [
            'nombre' => filled($config['nombre'] ?? null) ? mb_strtoupper((string) $config['nombre']) : 'Sin configurar',
            'rfc' => filled($config['rfc'] ?? null) ? mb_strtoupper((string) $config['rfc']) : 'Sin configurar',
            'regimen' => $this->regimen($config['regimen'] ?? null),
            'cp' => filled($config['cp'] ?? null) ? (string) $config['cp'] : 'Sin configurar',
        ];
    }

    /** Con RFC registrado se usa el del cliente; sin RFC aplica la regla de público en general. */
    private function receptor(PosVenta $venta, string $cpExpedicion): array
    {
        $cliente = $venta->cliente;
        if (filled($cliente?->cli_rfc)) {
            $nombre = $cliente->cli_razon_social ?: trim(implode(' ', array_filter([$cliente->cli_nombre, $cliente->cli_apellido_paterno, $cliente->cli_apellido_materno])));

            return [
                'nombre' => mb_strtoupper($nombre), 'rfc' => mb_strtoupper($cliente->cli_rfc),
                'regimen' => 'Sin registrar en el cliente', 'cp' => $cliente->cli_cp ?: 'Sin registrar',
                'uso' => 'G03 - Gastos en general',
            ];
        }

        return [
            'nombre' => 'PUBLICO EN GENERAL', 'rfc' => self::RFC_PUBLICO_GENERAL,
            'regimen' => $this->regimen('616'), 'cp' => $cpExpedicion, 'uso' => 'S01 - Sin efectos fiscales',
        ];
    }

    /**
     * Precios con IVA incluido → valor unitario sin IVA. Los descuentos generales y el crédito de cambio
     * se reparten entre conceptos (en CFDI 4.0 el descuento vive en cada concepto) y el último centavo de
     * redondeo se asienta en el IVA del concepto mayor para que el total coincida con el facturado.
     */
    private function conceptos(array $partidas, array $documento, float $tasa, float $totalFacturado): array
    {
        $factor = 1 + $tasa;
        $unidades = ProductoSku::query()->with('producto.unidad')->whereIn('psk_id', array_column($partidas, 'psk_id'))->get()
            ->mapWithKeys(fn ($sku) => [$sku->psk_id => strtoupper((string) $sku->producto?->unidad?->umd_codigo)]);
        $importes = $documento['importes'] ?? [];
        $general = round(((float) ($importes['descuento_global'] ?? 0) + (float) ($importes['credito_cambio'] ?? 0)) / $factor, 2);
        $netos = array_map(fn ($p) => (float) ($p['importe'] ?? 0), $partidas);
        $sumaNetos = array_sum($netos) ?: 1;
        $repartido = 0.0;
        $conceptos = [];
        foreach (array_values($partidas) as $i => $p) {
            $cantidad = (float) ($p['cantidad'] ?? 0);
            $valorUnitario = round((float) ($p['precio'] ?? 0) / $factor, 6);
            $importe = round($cantidad * $valorUnitario, 2);
            $parteGeneral = $i === count($partidas) - 1 ? round($general - $repartido, 2) : round($general * $netos[$i] / $sumaNetos, 2);
            $repartido += $parteGeneral;
            $descuento = round((float) ($p['descuento'] ?? 0) / $factor + $parteGeneral, 2);
            $base = round($importe - $descuento, 2);
            [$claveUnidad, $unidad] = self::UNIDADES[$unidades[$p['psk_id']] ?? ''] ?? (floor($cantidad) == $cantidad ? self::UNIDADES['PZA'] : self::UNIDADES['M']);
            $conceptos[] = [
                'clave_prod_serv' => '01010101', 'no_identificacion' => (string) ($p['codigo'] ?? ''),
                'descripcion' => (string) ($p['nombre'] ?? 'Producto'), 'cantidad' => $cantidad,
                'clave_unidad' => $claveUnidad, 'unidad' => $unidad, 'valor_unitario' => $valorUnitario,
                'importe' => $importe, 'descuento' => $descuento, 'objeto_impuesto' => '02',
                'base' => $base, 'iva' => round($base * $tasa, 2),
            ];
        }
        if ($conceptos) {
            $total = round(array_sum(array_map(fn ($c) => $c['base'] + $c['iva'], $conceptos)), 2);
            $mayor = array_search(max(array_column($conceptos, 'base')), array_column($conceptos, 'base'), true);
            $conceptos[$mayor]['iva'] = round($conceptos[$mayor]['iva'] + $totalFacturado - $total, 2);
        }

        return $conceptos;
    }

    private function cadenaOriginal(array $c, array $emisor, array $receptor, array $conceptos, string $certificado): string
    {
        $m = fn ($v) => number_format((float) $v, 2, '.', '');
        $partes = [$c['version'], $c['serie'], $c['folio'], $c['fecha'], strtok($c['forma_pago'], ' '), $certificado,
            $m($c['subtotal']), $m($c['descuento']), 'MXN', $m($c['total']), 'I', '01', 'PUE', $c['lugar_expedicion'],
            $emisor['rfc'], $emisor['nombre'], strtok($emisor['regimen'], ' '),
            $receptor['rfc'], $receptor['nombre'], $receptor['cp'], strtok($receptor['regimen'], ' '), strtok($receptor['uso'], ' ')];
        foreach ($conceptos as $k) {
            array_push($partes, $k['clave_prod_serv'], $k['no_identificacion'], rtrim(rtrim(number_format($k['cantidad'], 6, '.', ''), '0'), '.'),
                $k['clave_unidad'], $k['unidad'], $k['descripcion'], number_format($k['valor_unitario'], 6, '.', ''),
                $m($k['importe']), $m($k['descuento']), $k['objeto_impuesto'], $m($k['base']), '002', 'Tasa', number_format($c['tasa'], 6, '.', ''), $m($k['iva']));
        }
        $partes[] = $m($c['iva']);

        return '||'.implode('|', $partes).'||';
    }

    /** 256 bytes pseudoaleatorios derivados del texto, en base64 (344 caracteres, como un sello RSA-2048). No es una firma. */
    private function sello(string $texto): string
    {
        $bytes = '';
        for ($i = 0; strlen($bytes) < 256; $i++) {
            $bytes .= hash('sha512', $i.'|'.$texto, true);
        }

        return base64_encode(substr($bytes, 0, 256));
    }

    private function uuid(string $semilla): string
    {
        $h = hash('sha256', $semilla.'|uuid');
        $h[12] = '4';
        $h[16] = dechex(8 | (hexdec($h[16]) & 3));

        return strtoupper(substr($h, 0, 8).'-'.substr($h, 8, 4).'-'.substr($h, 12, 4).'-'.substr($h, 16, 4).'-'.substr($h, 20, 12));
    }

    private function digitos(string $semilla, int $largo): string
    {
        $digitos = preg_replace('/\D/', '', hash('sha512', $semilla).hash('sha512', $semilla.'|2'));

        return substr(str_pad($digitos, $largo, '0'), 0, $largo);
    }

    public function totalConLetra(float $total): string
    {
        $entero = (int) floor($total + 0.000001);
        $centavos = (int) round(($total - $entero) * 100);
        $texto = $entero === 0 ? 'CERO' : $this->letras($entero);
        $texto = $this->apocope($texto);

        return trim($texto).' '.($entero === 1 ? 'PESO' : 'PESOS').' '.str_pad((string) $centavos, 2, '0', STR_PAD_LEFT).'/100 M.N.';
    }

    /** "veintiuno" y "uno" se acortan delante de "mil", "millones" o "pesos". */
    private function apocope(string $texto): string
    {
        return preg_replace(['/VEINTIUNO$/u', '/UNO$/u'], ['VEINTIÚN', 'UN'], $texto);
    }

    private function letras(int $n): string
    {
        $unidades = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE', 'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE',
            'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE', 'VEINTE', 'VEINTIUNO', 'VEINTIDÓS', 'VEINTITRÉS', 'VEINTICUATRO', 'VEINTICINCO',
            'VEINTISÉIS', 'VEINTISIETE', 'VEINTIOCHO', 'VEINTINUEVE'];
        $decenas = ['', '', '', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];
        if ($n >= 1000000) {
            $millones = intdiv($n, 1000000);
            $resto = $n % 1000000;

            return trim(($millones === 1 ? 'UN MILLÓN' : $this->apocope($this->letras($millones)).' MILLONES').' '.($resto ? $this->letras($resto) : ''));
        }
        if ($n >= 1000) {
            $miles = intdiv($n, 1000);
            $resto = $n % 1000;

            return trim(($miles === 1 ? 'MIL' : $this->apocope($this->letras($miles)).' MIL').' '.($resto ? $this->letras($resto) : ''));
        }
        if ($n >= 100) {
            return $n === 100 ? 'CIEN' : trim($centenas[intdiv($n, 100)].' '.$this->letras($n % 100));
        }
        if ($n < 30) {
            return $unidades[$n];
        }

        return $decenas[intdiv($n, 10)].($n % 10 ? ' Y '.$unidades[$n % 10] : '');
    }
}
