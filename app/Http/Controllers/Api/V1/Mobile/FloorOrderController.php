<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Models\PedidoPiso;
use App\Models\Usuario;
use App\Services\Operacion\PedidoPisoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FloorOrderController extends Controller
{
    public function __construct(private readonly PedidoPisoService $pedidoPisoService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $usuario = $this->mobileUser($request);
        $this->requirePermission($usuario, 'pedido_piso.ver');
        $datos = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'paid', 'cancelled', 'all'])],
            'q' => ['nullable', 'string', 'max:120'],
            'branch_id' => ['nullable', 'integer'],
        ]);
        $sucursalIds = $this->assignedBranchIds($usuario);
        $sucursalId = isset($datos['branch_id']) ? (int) $datos['branch_id'] : null;
        if ($sucursalId !== null && !in_array($sucursalId, $sucursalIds, true)) {
            throw ValidationException::withMessages([
                'branch_id' => ['La sucursal seleccionada no está asignada a tu usuario.'],
            ]);
        }
        $estatus = match ((string) ($datos['status'] ?? 'pending')) {
            'paid' => 'cobrado',
            'cancelled' => 'cancelado',
            'all' => null,
            default => 'pendiente_cobro',
        };
        $consulta = trim((string) ($datos['q'] ?? ''));

        $pedidos = PedidoPiso::query()
            ->withDeleted()
            ->with([
                'almacen:alm_id,alm_nombre',
                'cliente:cli_id,cli_nombre,cli_apellido_paterno,cli_apellido_materno,cli_razon_social',
            ])
            ->withSum('detalle as item_count', 'ppd_cantidad')
            ->where('pdp_usr_id', $usuario->usr_id)
            ->whereIn('pdp_scl_id', $sucursalIds)
            ->when($sucursalId !== null, fn ($query) => $query->where('pdp_scl_id', $sucursalId))
            ->when($estatus, fn ($query, string $value) => $query->where('pdp_estatus', $value))
            ->when($consulta !== '', function ($query) use ($consulta): void {
                $query->where(function ($subquery) use ($consulta): void {
                    $subquery->where('pdp_folio', 'like', "%{$consulta}%")
                        ->orWhereHas('cliente', function ($clienteQuery) use ($consulta): void {
                            $clienteQuery->where('cli_nombre', 'like', "%{$consulta}%")
                                ->orWhere('cli_apellido_paterno', 'like', "%{$consulta}%")
                                ->orWhere('cli_razon_social', 'like', "%{$consulta}%");
                        });
                });
            })
            ->orderByDesc('pdp_id')
            ->limit(100)
            ->get();

        $cancelledIds = $pedidos
            ->where('pdp_estatus', 'cancelado')
            ->pluck('pdp_id')
            ->map(fn ($id): int => (int) $id)
            ->values();
        if ($cancelledIds->isNotEmpty()) {
            $latestDeletion = DB::table('tbl_pedido_piso_detalle_ppd')
                ->select('ppd_pdp_id', DB::raw('MAX(ppd_deleted_at) as latest_deleted_at'))
                ->whereIn('ppd_pdp_id', $cancelledIds)
                ->whereNotNull('ppd_deleted_at')
                ->groupBy('ppd_pdp_id');
            $cancelledCounts = DB::table('tbl_pedido_piso_detalle_ppd as detail')
                ->joinSub($latestDeletion, 'latest', function ($join): void {
                    $join->on('latest.ppd_pdp_id', '=', 'detail.ppd_pdp_id')
                        ->on('latest.latest_deleted_at', '=', 'detail.ppd_deleted_at');
                })
                ->select('detail.ppd_pdp_id', DB::raw('SUM(detail.ppd_cantidad) as item_count'))
                ->groupBy('detail.ppd_pdp_id')
                ->pluck('item_count', 'detail.ppd_pdp_id');

            $pedidos->each(function (PedidoPiso $pedido) use ($cancelledCounts): void {
                if ((string) $pedido->pdp_estatus === 'cancelado') {
                    $pedido->setAttribute('item_count', (float) ($cancelledCounts[$pedido->pdp_id] ?? 0));
                }
            });
        }

        $pedidos = $pedidos
            ->map(fn (PedidoPiso $pedido): array => $this->mapSummary($pedido))
            ->values();

        return response()->json(['data' => $pedidos]);
    }

    public function store(Request $request): JsonResponse
    {
        $usuario = $this->mobileUser($request);
        $this->requirePermission($usuario, 'pedido_piso.crear');
        $datos = $request->validate([
            'request_id' => ['required', 'uuid'],
            'branch_id' => ['required', 'integer'],
            'customer_id' => [
                'nullable',
                'integer',
                Rule::exists('tbl_clientes_cli', 'cli_id')->where(fn ($query) => $query
                    ->where('cli_estatus', 'activo')
                    ->where('cli_deleted', false)
                    ->whereNull('cli_deleted_at')),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.sku_id' => ['required', 'integer', 'exists:tbl_producto_skus_psk,psk_id'],
            'lines.*.warehouse_id' => ['required', 'integer', 'exists:tbl_almacenes_alm,alm_id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.discount_type' => ['nullable', Rule::in(['none', 'percentage', 'amount'])],
            'lines.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'lines.*.discount_quantity' => ['nullable', 'numeric', 'min:0'],
        ]);
        $sucursalId = $this->assignedBranchId($usuario, (int) $datos['branch_id']);

        $pedidosInput = collect($datos['lines'])
            ->groupBy(fn (array $linea): int => (int) $linea['warehouse_id'])
            ->map(function (Collection $lineas, int $almacenId) use ($datos, $sucursalId, $usuario): array {
                return [
                    'pdp_scl_id' => $sucursalId,
                    'pdp_alm_id' => $almacenId,
                    'pdp_cli_id' => !empty($datos['customer_id']) ? (int) $datos['customer_id'] : null,
                    'pdp_observaciones' => $datos['notes'] ?? null,
                    'partidas' => $lineas->map(function (array $linea) use ($usuario): array {
                        $descuentoTipo = match ((string) ($linea['discount_type'] ?? 'none')) {
                            'percentage' => 'porcentaje',
                            'amount' => 'importe',
                            default => 'ninguno',
                        };

                        return [
                            'ppd_psk_id' => (int) $linea['sku_id'],
                            'ppd_cantidad' => (float) $linea['quantity'],
                            'ppd_usr_id' => (int) $usuario->usr_id,
                            'ppd_descuento_tipo' => $descuentoTipo,
                            'ppd_descuento_valor' => (float) ($linea['discount_value'] ?? 0),
                            'ppd_descuento_cantidad' => (float) ($linea['discount_quantity'] ?? $linea['quantity']),
                        ];
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();

        $pedidos = $this->pedidoPisoService->crearLoteMobile(
            $request,
            (string) $datos['request_id'],
            $pedidosInput,
        )->map(fn (PedidoPiso $pedido): array => $this->mapDetail(
            $this->pedidoPisoService->obtenerPorId((int) $pedido->pdp_id, true),
        ));

        return response()->json([
            'message' => $pedidos->count() === 1
                ? 'Pedido generado correctamente.'
                : 'Pedidos generados correctamente.',
            'data' => [
                'request_id' => (string) $datos['request_id'],
                'orders' => $pedidos->values(),
            ],
        ], 201);
    }

    public function show(Request $request, int $order): JsonResponse
    {
        $usuario = $this->mobileUser($request);
        $this->requirePermission($usuario, 'pedido_piso.ver');
        $pedido = PedidoPiso::query()
            ->withDeleted()
            ->where('pdp_id', $order)
            ->where('pdp_usr_id', $usuario->usr_id)
            ->whereIn('pdp_scl_id', $this->assignedBranchIds($usuario))
            ->firstOrFail();

        return response()->json([
            'data' => $this->mapDetail($this->pedidoPisoService->obtenerPorId((int) $pedido->pdp_id, true)),
        ]);
    }

    public function update(Request $request, int $order): JsonResponse
    {
        $usuario = $this->mobileUser($request);
        $this->requirePermission($usuario, 'pedido_piso.crear');
        $datos = $request->validate([
            'branch_id' => ['required', 'integer'],
            'customer_id' => [
                'nullable',
                'integer',
                Rule::exists('tbl_clientes_cli', 'cli_id')->where(fn ($query) => $query
                    ->where('cli_estatus', 'activo')
                    ->where('cli_deleted', false)
                    ->whereNull('cli_deleted_at')),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.sku_id' => ['required', 'integer', 'exists:tbl_producto_skus_psk,psk_id'],
            'lines.*.warehouse_id' => ['required', 'integer', 'exists:tbl_almacenes_alm,alm_id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.discount_type' => ['nullable', Rule::in(['none', 'percentage', 'amount'])],
            'lines.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'lines.*.discount_quantity' => ['nullable', 'numeric', 'min:0'],
        ]);

        $pedido = PedidoPiso::query()
            ->where('pdp_id', $order)
            ->where('pdp_usr_id', $usuario->usr_id)
            ->whereIn('pdp_scl_id', $this->assignedBranchIds($usuario))
            ->firstOrFail();

        if ((int) $datos['branch_id'] !== (int) $pedido->pdp_scl_id) {
            throw ValidationException::withMessages([
                'branch_id' => ['El pedido solo puede editarse dentro de su sucursal original.'],
            ]);
        }

        $warehouseIds = collect($datos['lines'])
            ->pluck('warehouse_id')
            ->map(fn ($value): int => (int) $value)
            ->unique()
            ->values();
        if ($warehouseIds->count() !== 1 || $warehouseIds->first() !== (int) $pedido->pdp_alm_id) {
            throw ValidationException::withMessages([
                'lines' => ['Todos los productos deben pertenecer al almacén original del pedido.'],
            ]);
        }

        $partidas = collect($datos['lines'])->map(fn (array $linea): array => [
            'ppd_psk_id' => (int) $linea['sku_id'],
            'ppd_cantidad' => (float) $linea['quantity'],
            'ppd_descuento_tipo' => match ((string) ($linea['discount_type'] ?? 'none')) {
                'percentage' => 'porcentaje',
                'amount' => 'importe',
                default => 'ninguno',
            },
            'ppd_descuento_valor' => (float) ($linea['discount_value'] ?? 0),
            'ppd_descuento_cantidad' => (float) ($linea['discount_quantity'] ?? $linea['quantity']),
            'ppd_usr_id' => (int) $usuario->usr_id,
        ])->values()->all();

        $this->pedidoPisoService->actualizar($request, (int) $pedido->pdp_id, [
            'pdp_scl_id' => (int) $pedido->pdp_scl_id,
            'pdp_alm_id' => (int) $pedido->pdp_alm_id,
            'pdp_cli_id' => $datos['customer_id'] ?? null,
            'pdp_observaciones' => $datos['notes'] ?? null,
            'partidas' => $partidas,
        ]);

        return response()->json([
            'message' => 'Pedido actualizado correctamente.',
            'data' => $this->mapDetail($this->pedidoPisoService->obtenerPorId((int) $pedido->pdp_id, true)),
        ]);
    }

    public function destroy(Request $request, int $order): JsonResponse
    {
        $usuario = $this->mobileUser($request);
        if (!$usuario->tienePermiso('pedido_piso.eliminar') && !$usuario->tienePermiso('pedido_piso.crear')) {
            abort(403, 'No tienes permiso para cancelar pedidos.');
        }

        $pedido = PedidoPiso::query()
            ->where('pdp_id', $order)
            ->where('pdp_usr_id', $usuario->usr_id)
            ->whereIn('pdp_scl_id', $this->assignedBranchIds($usuario))
            ->firstOrFail();

        $this->pedidoPisoService->eliminar($request, (int) $pedido->pdp_id);

        return response()->json(['message' => 'Pedido cancelado correctamente.']);
    }

    private function mapSummary(PedidoPiso $pedido): array
    {
        return [
            'id' => (int) $pedido->pdp_id,
            'folio' => (string) $pedido->pdp_folio,
            'status' => $this->mapStatus((string) $pedido->pdp_estatus),
            'customer' => $this->customerName($pedido) ?: 'Público general',
            'item_count' => (float) ($pedido->item_count ?? 0),
            'total' => (float) $pedido->pdp_total,
            'created_at' => optional($pedido->pdp_fecha)->toIso8601String(),
            'warehouse' => (string) ($pedido->almacen?->alm_nombre ?? ''),
        ];
    }

    private function mapDetail(PedidoPiso $pedido): array
    {
        $detalle = $this->mobileDetailLines($pedido);

        return [
            'id' => (int) $pedido->pdp_id,
            'folio' => (string) $pedido->pdp_folio,
            'status' => $this->mapStatus((string) $pedido->pdp_estatus),
            'customer' => $this->customerName($pedido) ?: 'Público general',
            'customer_id' => $pedido->pdp_cli_id ? (int) $pedido->pdp_cli_id : null,
            'subtotal' => (float) $pedido->pdp_subtotal,
            'total' => (float) $pedido->pdp_total,
            'notes' => (string) ($pedido->pdp_observaciones ?? ''),
            'created_at' => optional($pedido->pdp_fecha)->toIso8601String(),
            'branch_id' => (int) $pedido->pdp_scl_id,
            'branch' => (string) ($pedido->sucursal?->scl_nombre ?? ''),
            'warehouse_id' => (int) $pedido->pdp_alm_id,
            'warehouse' => (string) ($pedido->almacen?->alm_nombre ?? ''),
            'seller' => (string) ($pedido->usuario?->usr_nombre ?? ''),
            'lines' => $detalle->map(fn ($linea): array => [
                'id' => (int) $linea->ppd_id,
                'sku_id' => (int) $linea->ppd_psk_id,
                'sku' => (string) ($linea->sku?->psk_codigo ?? ''),
                'barcode' => (string) ($linea->sku?->psk_codigo_barras ?? ''),
                'name' => (string) (($linea->sku?->psk_nombre ?? '') ?: ($linea->sku?->producto?->prd_nombre ?? 'Producto')),
                'quantity' => (float) $linea->ppd_cantidad,
                'price' => (float) $linea->ppd_precio_unitario,
                'subtotal' => (float) $linea->ppd_importe,
                'discount' => (float) $linea->ppd_descuento_importe,
                'discount_type' => match ((string) ($linea->ppd_descuento_tipo ?? 'ninguno')) {
                    'porcentaje' => 'percentage',
                    'importe' => 'amount',
                    default => 'none',
                },
                'discount_value' => (float) ($linea->ppd_descuento_valor ?? 0),
                'discount_quantity' => (float) ($linea->ppd_descuento_cantidad ?? 0),
                'total' => (float) ($linea->ppd_total_linea ?? $linea->ppd_importe),
                'unit' => (string) ($linea->sku?->producto?->unidad?->umd_nombre ?? ''),
                'unit_code' => (string) ($linea->sku?->producto?->unidad?->umd_codigo ?? ''),
                'allows_decimal' => strtoupper(trim((string) ($linea->sku?->producto?->unidad?->umd_codigo ?? ''))) === 'M',
            ])->values(),
        ];
    }

    private function mobileDetailLines(PedidoPiso $pedido): Collection
    {
        if ((string) $pedido->pdp_estatus !== 'cancelado' || $pedido->detalle->isNotEmpty()) {
            return $pedido->detalle;
        }

        $historical = $pedido->detalleConEliminados()
            ->with([
                'sku:psk_id,psk_prd_id,psk_codigo,psk_codigo_barras,psk_nombre,psk_precio',
                'capturista:usr_id,usr_nombre,usr_usuario',
                'sku.producto:prd_id,prd_umd_id,prd_nombre',
                'sku.producto.unidad:umd_id,umd_codigo,umd_nombre',
            ])
            ->whereNotNull('ppd_deleted_at')
            ->get();
        $latestDeletion = $historical->max(fn ($linea): string => (string) $linea->ppd_deleted_at);

        return $historical
            ->filter(fn ($linea): bool => (string) $linea->ppd_deleted_at === $latestDeletion)
            ->values();
    }

    private function mapStatus(string $status): string
    {
        return match ($status) {
            'cobrado' => 'paid',
            'cancelado' => 'cancelled',
            default => 'pending',
        };
    }

    private function customerName(PedidoPiso $pedido): string
    {
        if (!$pedido->cliente) {
            return '';
        }

        return trim((string) ($pedido->cliente->cli_razon_social
            ?: implode(' ', array_filter([
                $pedido->cliente->cli_nombre,
                $pedido->cliente->cli_apellido_paterno,
                $pedido->cliente->cli_apellido_materno,
            ]))));
    }

    private function mobileUser(Request $request): Usuario
    {
        /** @var Usuario|null $usuario */
        $usuario = $request->user();
        if (!$usuario || !$usuario->tokenCan('mobile:orders')) {
            abort(403, 'Tu sesión no tiene acceso a pedidos móviles.');
        }

        return $usuario;
    }

    private function requirePermission(Usuario $usuario, string $permission): void
    {
        if (!$usuario->tienePermiso($permission)) {
            abort(403, 'No tienes permiso para realizar esta acción.');
        }
    }

    private function assignedBranchId(Usuario $usuario, int $sucursalId): int
    {
        if (!in_array($sucursalId, $this->assignedBranchIds($usuario), true)) {
            throw ValidationException::withMessages([
                'branch_id' => ['La sucursal seleccionada no está asignada a tu usuario.'],
            ]);
        }

        return $sucursalId;
    }

    private function assignedBranchIds(Usuario $usuario): array
    {
        return $usuario->sucursales()
            ->where('tbl_sucursales_scl.scl_estatus', 'activo')
            ->pluck('tbl_sucursales_scl.scl_id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }
}
