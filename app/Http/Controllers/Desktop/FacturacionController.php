<?php

namespace App\Http\Controllers\Desktop;

use App\Http\Controllers\Controller;
use App\Models\PosVenta;
use App\Services\Operacion\FacturacionPdfService;
use App\Services\Operacion\FacturacionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class FacturacionController extends Controller
{
    private const SITUACIONES = ['requiere_ajuste', 'lista', 'simulada', 'revision'];

    public function __construct(private readonly FacturacionService $service, private readonly FacturacionPdfService $pdf) {}

    public function index(Request $request)
    {
        return view('desktop.facturacion.index', [
            'sinAlmacenHabilitado' => $this->service->sucursalesConAlmacen($request->user()) === [],
            'puedeConfigurarAlmacenes' => $request->user()->tienePermiso('almacen.ver'),
        ]);
    }

    public function data(Request $request)
    {
        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:120'], 'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'estado' => ['nullable', 'in:requiere_ajuste,lista,ajustada,simulada,revision,pendiente'],
            'page' => ['nullable', 'integer', 'min:1'],
        ], ['hasta.after_or_equal' => 'La fecha final debe ser igual o posterior a la inicial.']);
        $base = fn () => $this->service->ventas($request->user())
            ->when($filtros['buscar'] ?? null, fn ($q, $buscar) => $q->where(function ($sub) use ($buscar) {
                $sub->where('psv_folio', 'like', '%'.$buscar.'%')
                    ->orWhereHas('cliente', fn ($cli) => $cli->where('cli_nombre', 'like', '%'.$buscar.'%')
                        ->orWhere('cli_apellido_paterno', 'like', '%'.$buscar.'%'));
            }))
            ->when($filtros['desde'] ?? null, fn ($q, $fecha) => $q->whereDate('psv_fecha_cobro', '>=', $fecha))
            ->when($filtros['hasta'] ?? null, fn ($q, $fecha) => $q->whereDate('psv_fecha_cobro', '<=', $fecha));

        $ventas = $base()
            ->withCount([
                'cambiosRelacionados as cambios_activos' => fn ($q) => $q->where('psv_estatus', '!=', 'cancelada'),
                'creditosCambioGenerados as creditos_activos' => fn ($q) => $q->where('pcc_estatus', '!=', 'cancelado'),
            ])
            ->when($filtros['estado'] ?? null, fn (Builder $q, $estado) => $estado === 'pendiente'
                ? $q->whereDoesntHave('facturacion')
                : $this->service->filtrarSituacion($q, $estado))
            ->orderByDesc('psv_id')->paginate(25);

        $conAlmacen = $this->service->sucursalesConAlmacen($request->user());
        $ventas->through(function (PosVenta $venta) use ($conAlmacen) {
            $mixta = $this->service->esMixta($venta);
            $motivo = $this->service->motivoBloqueo($venta);

            return [
                'id' => $venta->psv_id, 'folio' => $venta->psv_folio, 'fecha' => $venta->psv_fecha_cobro?->format('d/m/Y H:i'),
                'cliente' => $this->cliente($venta), 'total' => $venta->psv_total,
                'almacenes' => $venta->detalle->map(fn ($d) => $d->almacen?->alm_nombre ?? $venta->almacen?->alm_nombre ?? '—')->unique()->values(),
                'mixta' => $mixta, 'bloqueada' => $motivo !== null, 'motivo' => $motivo,
                'situacion' => $this->service->situacion($venta, $motivo !== null),
                'sin_almacen' => $mixta && ! in_array((int) $venta->psv_scl_id, $conAlmacen, true),
                'estado' => $venta->facturacion?->fac_estado ?? 'pendiente', 'folio_factura' => $venta->facturacion?->fac_folio,
                'total_ajustado' => $venta->facturacion?->fac_total, 'diferencia' => $venta->facturacion?->fac_diferencia,
                'version' => $venta->facturacion?->fac_version ?? 0,
            ];
        });

        // Conteos por situación con los mismos filtros de búsqueda y fechas, para los accesos rápidos.
        $resumen = collect(self::SITUACIONES)->mapWithKeys(fn ($situacion) => [
            $situacion => $this->service->filtrarSituacion($base(), $situacion)->count(),
        ]);

        return response()->json($ventas->toArray() + ['resumen' => $resumen]);
    }

    public function show(Request $request, int $venta)
    {
        $registro = $this->service->ventas($request->user())->findOrFail($venta);
        $factura = $registro->facturacion;
        $mixta = $this->service->esMixta($registro);
        $motivo = $this->service->motivoBloqueo($registro);
        $partidas = $factura?->fac_partidas ?? ($mixta ? [] : $this->service->original($registro));

        // Existencia de consulta para las partidas guardadas; no reserva ni descuenta mercancía.
        if ($mixta && $factura?->fac_alm_id && $partidas) {
            $existencias = $this->service->productos((int) $factura->fac_alm_id)
                ->whereIn('psk_id', array_column($partidas, 'psk_id'))->get()
                ->mapWithKeys(fn ($sku) => [$sku->psk_id => (float) $sku->existenciasAlmacen->sum('exa_existencia')]);
            $partidas = array_map(fn ($p) => $p + ['existencia' => $existencias[$p['psk_id']] ?? null, 'disponible' => $existencias->has($p['psk_id'])], $partidas);
        }

        // Volver regresa al listado con sus filtros solo si de ahí se llegó.
        $anterior = url()->previous();
        $listado = route('desktop.facturacion.index');
        $volver = strtok($anterior, '?') === $listado ? $anterior : $listado;

        return view('desktop.facturacion.ajuste', [
            'venta' => $registro, 'cliente' => $this->cliente($registro),
            'original' => $factura?->fac_original ?? $this->service->original($registro),
            'partidas' => $partidas,
            'almacenes' => $this->service->almacenes($registro)->orderBy('alm_nombre')->get(),
            'mixta' => $mixta, 'bloqueo' => $motivo,
            'situacion' => $this->service->situacion($registro, $motivo !== null),
            'volver' => $volver,
            'puedeConfigurarAlmacenes' => $request->user()->tienePermiso('almacen.ver'),
        ]);
    }

    public function productos(Request $request, int $venta)
    {
        $datos = $request->validate([
            'almacen_id' => ['required', 'integer'], 'buscar' => ['nullable', 'string', 'max:120'],
            'ids' => ['nullable', 'array', 'max:200'], 'ids.*' => ['integer'],
        ]);
        $registro = $this->service->ventas($request->user())->findOrFail($venta);
        abort_unless($this->service->almacenes($registro)->whereKey($datos['almacen_id'])->exists(), 422, 'El almacén no está habilitado para facturar en esta sucursal.');
        // Los lectores con distribución de teclado distinta envían ' en lugar de -, igual que en Punto de venta.
        $buscar = trim(str_replace("'", '-', (string) ($datos['buscar'] ?? '')));
        $terminos = array_slice(array_filter(preg_split('/\s+/', $buscar)), 0, 6);
        $skus = $this->service->productos((int) $datos['almacen_id'])
            ->when($terminos, function ($q) use ($terminos, $buscar) {
                foreach ($terminos as $termino) {
                    $q->where(fn ($s) => $s->where('psk_nombre', 'like', '%'.$termino.'%')
                        ->orWhere('psk_codigo', 'like', '%'.$termino.'%')
                        ->orWhere('psk_codigo_barras', 'like', '%'.$termino.'%'));
                }
                $q->orderByRaw('case when psk_codigo = ? or psk_codigo_barras = ? then 0 else 1 end', [$buscar, $buscar]);
            })
            // Con ids se consultan partidas concretas (copiar del original), siempre dentro del almacén elegido.
            ->when($datos['ids'] ?? null, fn ($q, $ids) => $q->whereIn('psk_id', $ids))
            ->orderBy('psk_nombre')->limit(empty($datos['ids']) ? 40 : 200)->get();

        return response()->json(['data' => $skus->map(fn ($sku) => [
            'psk_id' => $sku->psk_id, 'codigo' => $sku->psk_codigo, 'codigo_barras' => $sku->psk_codigo_barras,
            'nombre' => $sku->psk_nombre,
            'precio' => (float) $sku->psk_precio, 'existencia' => (float) $sku->existenciasAlmacen->sum('exa_existencia'),
            'decimal' => strtoupper($sku->producto?->unidad?->umd_codigo ?? '') === 'M',
            'exacto' => $buscar !== '' && in_array($buscar, [$sku->psk_codigo, $sku->psk_codigo_barras], true),
        ])]);
    }

    public function guardar(Request $request, int $venta)
    {
        $datos = $request->validate([
            'almacen_id' => ['required', 'integer'], 'version' => ['required', 'integer', 'min:0'],
            'notas' => ['nullable', 'string', 'max:2000'], 'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.psk_id' => ['required', 'integer', 'distinct'],
            'items.*.cantidad' => ['required', 'numeric', 'min:0.01', 'max:999999', 'decimal:0,2'],
            'items.*.precio' => ['required', 'numeric', 'min:0', 'max:999999', 'decimal:0,2'],
        ], [
            'almacen_id.required' => 'Selecciona el almacén habilitado para facturar.',
            'items.required' => 'Agrega al menos un producto al ticket ajustado.',
            'items.min' => 'Agrega al menos un producto al ticket ajustado.',
            'items.max' => 'El ticket ajustado admite hasta 200 partidas.',
            'items.*.psk_id.distinct' => 'Este producto está repetido; ajusta la cantidad en una sola partida.',
            'items.*.cantidad.required' => 'Captura la cantidad.',
            'items.*.cantidad.numeric' => 'Captura una cantidad válida.',
            'items.*.cantidad.min' => 'La cantidad debe ser mayor a cero.',
            'items.*.cantidad.max' => 'La cantidad es demasiado alta.',
            'items.*.cantidad.decimal' => 'Usa máximo dos decimales.',
            'items.*.precio.required' => 'Captura el precio.',
            'items.*.precio.numeric' => 'Captura un precio válido.',
            'items.*.precio.min' => 'El precio no puede ser negativo.',
            'items.*.precio.max' => 'El precio es demasiado alto.',
            'items.*.precio.decimal' => 'Usa máximo dos decimales.',
            'notas.max' => 'Las observaciones admiten hasta 2000 caracteres.',
        ]);

        return response()->json(['message' => 'Ajuste guardado. No se movió inventario.', 'data' => $this->service->guardar($request, $venta, $datos)]);
    }

    public function emitir(Request $request, int $venta)
    {
        $datos = $request->validate(['version' => ['required', 'integer', 'min:0']]);

        return response()->json(['message' => 'Factura simulada generada.', 'data' => $this->service->emitir($request, $venta, (int) $datos['version'])]);
    }

    /** Ver o descargar el PDF de una factura ya emitida; solo lectura, con el mismo folio en cada descarga. */
    public function pdf(Request $request, int $venta)
    {
        $registro = $this->service->ventas($request->user())->findOrFail($venta);
        $factura = $registro->facturacion;
        abort_unless($factura?->fac_estado === 'simulada', 404, 'La venta aún no tiene factura simulada.');
        $disposicion = $request->boolean('descargar') ? 'attachment' : 'inline';

        return response($this->pdf->generar($registro, $factura), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposicion.'; filename="'.$this->pdf->nombreArchivo($factura).'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function cliente(PosVenta $venta): string
    {
        return trim(implode(' ', array_filter([
            $venta->cliente?->cli_nombre, $venta->cliente?->cli_apellido_paterno, $venta->cliente?->cli_apellido_materno,
        ]))) ?: 'Público general';
    }
}
