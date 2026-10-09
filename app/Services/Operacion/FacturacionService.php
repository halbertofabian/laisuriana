<?php

namespace App\Services\Operacion;

use App\Models\Almacen;
use App\Models\Facturacion;
use App\Models\PosVenta;
use App\Models\ProductoSku;
use App\Models\Sucursal;
use App\Models\Usuario;
use App\Services\AuditoriaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FacturacionService
{
    public function __construct(private readonly AuditoriaService $auditoria, private readonly CfdiSimuladoService $cfdi) {}

    public function ventas(Usuario $usuario): Builder
    {
        $query = PosVenta::query()->with(['detalle.sku', 'detalle.almacen', 'almacen', 'cliente', 'facturacion']);
        if (! $this->esAdministrador($usuario)) {
            $query->whereIn('psv_scl_id', $usuario->sucursales()->select('scl_id'));
        }

        return $query;
    }

    public function esAdministrador(Usuario $usuario): bool
    {
        return $usuario->roles()->whereIn('rol_nombre', ['Administrador', 'Administrador del Sistema'])->exists();
    }

    public function esMixta(PosVenta $venta): bool
    {
        return $venta->detalle->map(fn ($linea) => (int) ($linea->pvd_alm_id ?: $venta->psv_alm_id))->unique()->count() > 1;
    }

    /**
     * Datos del documento impreso tal como estaban al emitir. Las partidas y el total salen de la
     * factura (ajustada o directa); de la venta solo se toman referencia, cliente y descuentos registrados.
     */
    public function documento(PosVenta $venta, Facturacion $factura): array
    {
        $directa = $factura->fac_inventario_estado === 'sin_ajuste';
        $partidas = collect($factura->fac_partidas ?? []);
        $subtotal = round($partidas->sum(fn ($p) => (float) ($p['importe'] ?? 0)), 2);
        $cliente = $venta->cliente;
        $domicilio = $cliente ? trim(implode(', ', array_filter([
            trim(implode(' ', array_filter([$cliente->cli_calle, $cliente->cli_num_ext ? 'No. '.$cliente->cli_num_ext : null, $cliente->cli_num_int ? 'Int. '.$cliente->cli_num_int : null]))),
            $cliente->cli_colonia, $cliente->cli_cp ? 'C.P. '.$cliente->cli_cp : null,
            $cliente->cli_ciudad ?: $cliente->cli_municipio, $cliente->cli_estado,
        ]))) : '';
        $nombre = $cliente ? trim(implode(' ', array_filter([$cliente->cli_nombre, $cliente->cli_apellido_paterno, $cliente->cli_apellido_materno]))) : '';

        $documento = [
            'origen' => $directa ? 'directa' : 'ajuste',
            'cliente' => array_filter([
                'nombre' => $nombre ?: 'Público general', 'razon_social' => $cliente?->cli_razon_social,
                'rfc' => $cliente?->cli_rfc, 'email' => $cliente?->cli_email,
                'telefono' => $cliente?->cli_telefono, 'domicilio' => $domicilio,
            ], fn ($valor) => filled($valor)),
            'venta' => [
                'folio' => $venta->psv_folio, 'fecha' => $venta->psv_fecha_cobro?->format('d/m/Y H:i'),
                'sucursal' => Sucursal::query()->whereKey($venta->psv_scl_id)->value('scl_nombre'),
            ],
            'almacen' => Almacen::query()->whereKey($factura->fac_alm_id)->value('alm_nombre'),
            // Solo descuentos y créditos realmente registrados en la venta; un ticket ajustado no los hereda.
            'importes' => [
                'descuento_partidas' => $directa ? round($partidas->sum(fn ($p) => (float) ($p['descuento'] ?? 0)), 2) : 0.0,
                'subtotal' => $subtotal,
                'descuento_global' => $directa ? round((float) $venta->psv_descuento, 2) : 0.0,
                'credito_cambio' => $directa ? round((float) $venta->psv_credito_cambio, 2) : 0.0,
                'total' => round((float) $factura->fac_total, 2),
            ],
        ];

        return $documento + ['cfdi' => $this->cfdi->generar($venta, $factura, $documento)];
    }

    /** Situación operativa de la venta para el mostrador, en el mismo orden en que se atiende. */
    public function situacion(PosVenta $venta, bool $bloqueada): string
    {
        $estado = $venta->facturacion?->fac_estado;
        if ($estado === 'simulada') {
            return 'simulada';
        }
        if ($bloqueada) {
            return 'revision';
        }
        if ($estado === 'ajustada') {
            return 'ajustada';
        }

        return $this->esMixta($venta) ? 'requiere_ajuste' : 'lista';
    }

    /** Motivo breve por el que una venta no puede facturarse; null si es facturable. */
    public function motivoBloqueo(PosVenta $venta): ?string
    {
        if ($venta->psv_estatus === 'cancelada' || $venta->psv_cancelado_at) {
            return 'Venta cancelada.';
        }
        if ($venta->psv_tipo_operacion !== 'venta') {
            return 'Solo se facturan ventas, no cambios ni devoluciones.';
        }
        if ($venta->psv_estatus !== 'cobrada') {
            return 'La venta todavía no está cobrada.';
        }
        if ($venta->detalle->isEmpty()) {
            return 'La venta no tiene partidas.';
        }
        $cambios = $venta->cambios_activos ?? $venta->cambiosRelacionados()->where('psv_estatus', '!=', 'cancelada')->count();
        $creditos = $venta->creditos_activos ?? $venta->creditosCambioGenerados()->where('pcc_estatus', '!=', 'cancelado')->count();

        return ($cambios > 0 || $creditos > 0) ? 'Tiene cambios o devoluciones; revísala antes de facturar.' : null;
    }

    /** Filtra por situación en SQL, con la misma regla de esMixta() y motivoBloqueo(). */
    public function filtrarSituacion(Builder $query, string $situacion): Builder
    {
        $tabla = (new PosVenta)->getTable();
        $mixta = "(select count(distinct coalesce(nullif(d.pvd_alm_id, 0), {$tabla}.psv_alm_id)) from tbl_pos_venta_detalle_pvd d"
            ." where d.pvd_psv_id = {$tabla}.psv_id and d.pvd_deleted = ?) > 1";
        $facturable = fn (Builder $q) => $q->where('psv_estatus', 'cobrada')->whereNull('psv_cancelado_at')
            ->where('psv_tipo_operacion', 'venta')->whereHas('detalle')
            ->whereDoesntHave('cambiosRelacionados', fn ($c) => $c->where('psv_estatus', '!=', 'cancelada'))
            ->whereDoesntHave('creditosCambioGenerados', fn ($c) => $c->where('pcc_estatus', '!=', 'cancelado'));

        return match ($situacion) {
            'simulada' => $query->whereHas('facturacion', fn ($f) => $f->where('fac_estado', 'simulada')),
            'revision' => $query->whereDoesntHave('facturacion', fn ($f) => $f->where('fac_estado', 'simulada'))
                ->whereNot(fn ($q) => $facturable($q)),
            'requiere_ajuste' => $facturable($query)->whereDoesntHave('facturacion')->whereRaw($mixta, [false]),
            'ajustada' => $facturable($query)->whereHas('facturacion', fn ($f) => $f->where('fac_estado', 'ajustada')),
            'lista' => $facturable($query)->where(fn ($q) => $q
                ->whereHas('facturacion', fn ($f) => $f->where('fac_estado', 'ajustada'))
                ->orWhere(fn ($sub) => $sub->whereDoesntHave('facturacion')->whereRaw('not '.$mixta, [false]))),
            default => $query,
        };
    }

    /** Sucursales visibles para el usuario que ya tienen al menos un almacén habilitado para facturar. */
    public function sucursalesConAlmacen(Usuario $usuario): array
    {
        return Almacen::query()->where('alm_estatus', 'activo')->where('alm_permite_facturar', true)
            ->when(! $this->esAdministrador($usuario), fn ($q) => $q->whereIn('alm_scl_id', $usuario->sucursales()->select('scl_id')))
            ->distinct()->pluck('alm_scl_id')->map(fn ($id) => (int) $id)->all();
    }

    public function original(PosVenta $venta): array
    {
        return $venta->detalle->map(fn ($linea) => [
            'psk_id' => (int) $linea->pvd_psk_id,
            'codigo' => $linea->sku?->psk_codigo ?? '',
            'nombre' => $linea->sku?->psk_nombre ?? 'Producto no disponible',
            'almacen_id' => (int) ($linea->pvd_alm_id ?: $venta->psv_alm_id),
            'almacen' => $linea->almacen?->alm_nombre ?? $venta->almacen?->alm_nombre ?? '—',
            'cantidad' => (float) $linea->pvd_cantidad,
            'precio' => (float) $linea->pvd_precio_unitario,
            'descuento' => (float) $linea->pvd_descuento_importe,
            'importe' => (float) $linea->pvd_importe,
        ])->all();
    }

    public function almacenes(PosVenta $venta): Builder
    {
        return Almacen::query()->where('alm_scl_id', $venta->psv_scl_id)
            ->where('alm_estatus', 'activo')->where('alm_permite_facturar', true);
    }

    public function productos(int $almacenId): Builder
    {
        return ProductoSku::query()->where('psk_estatus', 'activo')
            ->whereHas('producto', fn ($q) => $q->where('prd_estatus', 'activo')
                ->whereHas('almacenesPermitidos', fn ($alm) => $alm->where('alm_id', $almacenId)))
            ->with(['producto.unidad', 'existenciasAlmacen' => fn ($q) => $q->where('exa_alm_id', $almacenId)->where('exa_estatus', 'activo')]);
    }

    public function validarVenta(PosVenta $venta): void
    {
        if ($venta->psv_estatus !== 'cobrada' || $venta->psv_cancelado_at || $venta->psv_tipo_operacion !== 'venta') {
            throw ValidationException::withMessages(['venta' => 'Solo se pueden facturar ventas cobradas y no canceladas.']);
        }
        if ($venta->detalle->isEmpty()) {
            throw ValidationException::withMessages(['venta' => 'La venta no tiene partidas disponibles.']);
        }
        if ($venta->cambiosRelacionados()->where('psv_estatus', '!=', 'cancelada')->exists()
            || $venta->creditosCambioGenerados()->where('pcc_estatus', '!=', 'cancelado')->exists()) {
            throw ValidationException::withMessages(['venta' => 'Esta venta tiene cambios o devoluciones. Debe revisarse antes de facturar.']);
        }
    }

    private function validarVersion(?Facturacion $factura, int $version): void
    {
        if ((int) ($factura?->fac_version ?? 0) !== $version) {
            throw ValidationException::withMessages(['version' => 'El ticket cambió en otra sesión. Recarga la pantalla antes de continuar.']);
        }
    }

    private function prepararPartidas(PosVenta $venta, int $almacenId, array $items): array
    {
        if (! $this->almacenes($venta)->whereKey($almacenId)->exists()) {
            throw ValidationException::withMessages(['almacen_id' => 'Selecciona un almacén activo de esta sucursal habilitado para facturar.']);
        }
        $skus = $this->productos($almacenId)->whereIn('psk_id', array_column($items, 'psk_id'))->get()->keyBy('psk_id');

        // Además del error general se indica la partida, para señalarla junto a su campo.
        return collect($items)->map(function ($item, $indice) use ($skus) {
            $sku = $skus->get($item['psk_id']);
            if (! $sku) {
                $mensaje = 'Uno de los productos ya no está activo o no pertenece al almacén elegido.';
                throw ValidationException::withMessages(['items' => $mensaje, "items.{$indice}.psk_id" => 'Este producto ya no está disponible en el almacén elegido.']);
            }
            $cantidad = round((float) $item['cantidad'], 2);
            if (strtoupper($sku->producto?->unidad?->umd_codigo ?? '') !== 'M' && floor($cantidad) !== $cantidad) {
                throw ValidationException::withMessages(['items' => 'El producto '.$sku->psk_nombre.' requiere cantidades enteras.', "items.{$indice}.cantidad" => 'Usa una cantidad entera.']);
            }
            $precio = round((float) $item['precio'], 2);

            return [
                'psk_id' => (int) $sku->psk_id, 'codigo' => $sku->psk_codigo, 'nombre' => $sku->psk_nombre,
                'decimal' => strtoupper($sku->producto?->unidad?->umd_codigo ?? '') === 'M',
                'cantidad' => $cantidad, 'precio' => $precio, 'importe' => round($cantidad * $precio, 2),
            ];
        })->all();
    }

    public function guardar(Request $request, int $ventaId, array $datos): Facturacion
    {
        return DB::transaction(function () use ($request, $ventaId, $datos) {
            $venta = $this->ventas($request->user())->lockForUpdate()->findOrFail($ventaId);
            $this->validarVenta($venta);
            $factura = $venta->facturacion;
            $this->validarVersion($factura, (int) $datos['version']);
            if (! $this->esMixta($venta) || $factura?->fac_estado === 'simulada') {
                throw ValidationException::withMessages(['venta' => 'Esta venta no admite ajustes.']);
            }
            $partidas = $this->prepararPartidas($venta, (int) $datos['almacen_id'], $datos['items']);
            $total = round(array_sum(array_column($partidas, 'importe')), 2);
            if ($total > 999999999999.99) {
                throw ValidationException::withMessages(['items' => 'El total del ticket excede el importe permitido.']);
            }
            $factura = Facturacion::query()->updateOrCreate(['fac_psv_id' => $venta->psv_id], [
                'fac_alm_id' => $datos['almacen_id'], 'fac_estado' => 'ajustada',
                'fac_original' => $factura?->fac_original ?? $this->original($venta),
                'fac_partidas' => $partidas, 'fac_total_original' => $venta->psv_total,
                'fac_total' => $total, 'fac_diferencia' => round($total - (float) $venta->psv_total, 2),
                'fac_notas' => $datos['notas'] ?? null,
                'fac_version' => (int) ($factura?->fac_version ?? 0) + 1,
                'fac_created_by_usr_id' => $factura?->fac_created_by_usr_id ?? $request->user()->usr_id,
                'fac_updated_by_usr_id' => $request->user()->usr_id,
            ]);
            $this->auditoria->registrarAccion($request, 'facturacion.ajustar', $factura->getTable(), (string) $factura->fac_id, $factura->toArray());

            return $factura;
        });
    }

    public function emitir(Request $request, int $ventaId, int $version): Facturacion
    {
        return DB::transaction(function () use ($request, $ventaId, $version) {
            $venta = $this->ventas($request->user())->lockForUpdate()->findOrFail($ventaId);
            $this->validarVenta($venta);
            $factura = $venta->facturacion;
            // Los reintentos de emisión devuelven el mismo folio, sin duplicar documentos.
            if ($factura?->fac_estado === 'simulada') {
                return $factura;
            }
            $this->validarVersion($factura, $version);
            if ($this->esMixta($venta)) {
                if (! $factura) {
                    throw ValidationException::withMessages(['venta' => 'Debes guardar el ticket ajustado antes de facturar una venta de varios almacenes.']);
                }
                $this->prepararPartidas($venta, (int) $factura->fac_alm_id, $factura->fac_partidas);
            } else {
                $factura = Facturacion::query()->create([
                    'fac_psv_id' => $venta->psv_id, 'fac_alm_id' => $venta->detalle->first()->pvd_alm_id ?: $venta->psv_alm_id,
                    'fac_original' => $this->original($venta), 'fac_partidas' => $this->original($venta),
                    'fac_total_original' => $venta->psv_total, 'fac_total' => $venta->psv_total, 'fac_diferencia' => 0,
                    'fac_inventario_estado' => 'sin_ajuste', 'fac_created_by_usr_id' => $request->user()->usr_id,
                ]);
            }
            $factura->fill([
                'fac_estado' => 'simulada', 'fac_folio' => 'SIM-'.str_pad((string) $factura->fac_id, 8, '0', STR_PAD_LEFT),
                'fac_emitida_at' => now(), 'fac_updated_by_usr_id' => $request->user()->usr_id,
                'fac_version' => $factura->fac_version + 1,
            ]);
            // La foto del documento (incluido el timbre simulado) se toma con folio y fecha ya asignados.
            $factura->fac_documento = $this->documento($venta, $factura);
            $factura->save();
            $this->auditoria->registrarAccion($request, 'facturacion.emitir', $factura->getTable(), (string) $factura->fac_id, ['folio' => $factura->fac_folio, 'total' => $factura->fac_total, 'simulada' => true]);

            return $factura;
        });
    }
}
