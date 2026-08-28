<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\ProductoSku;
use App\Models\Usuario;
use App\Services\AuthService;
use App\Services\Operacion\EscaneoProductoService;
use App\Services\Operacion\ProductoAlmacenResolverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OrderCatalogController extends Controller
{
    public function __construct(
        private readonly EscaneoProductoService $escaneoProductoService,
        private readonly ProductoAlmacenResolverService $productoAlmacenResolverService,
    ) {
    }

    public function context(Request $request, AuthService $authService): JsonResponse
    {
        $usuario = $this->mobileUser($request);
        $sucursales = $this->assignedBranches($usuario);

        return response()->json([
            'data' => [
                'sucursal_predeterminada_id' => $authService->sucursalPredeterminadaId($usuario),
                'sucursales' => $sucursales,
            ],
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        $usuario = $this->mobileUser($request);
        $datos = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'branch_id' => ['required', 'integer'],
        ]);
        $sucursalId = $this->assignedBranchId($usuario, (int) $datos['branch_id']);
        $consulta = trim((string) ($datos['q'] ?? ''));

        if (mb_strlen($consulta) < 2) {
            return response()->json(['data' => []]);
        }

        $productos = collect($this->escaneoProductoService->sugerencias($consulta, 15))
            ->map(function (array $producto) use ($sucursalId): ?array {
                $resolucion = $this->productoAlmacenResolverService->resolverSkuAlmacen(
                    (int) $producto['psk_id'],
                    $sucursalId,
                );

                if (empty($resolucion['valido'])) {
                    return null;
                }

                return $this->mapProduct($producto, $resolucion);
            })
            ->filter()
            ->values();

        return response()->json(['data' => $productos]);
    }

    public function warehouses(Request $request, ProductoSku $sku): JsonResponse
    {
        $usuario = $this->mobileUser($request);
        $datos = $request->validate([
            'branch_id' => ['required', 'integer'],
        ]);
        $sucursalId = $this->assignedBranchId($usuario, (int) $datos['branch_id']);
        $resolucion = $this->productoAlmacenResolverService->resolverSkuAlmacen(
            (int) $sku->psk_id,
            $sucursalId,
        );

        if (empty($resolucion['valido'])) {
            return response()->json([
                'message' => (string) ($resolucion['message'] ?? 'El producto no está disponible en esta sucursal.'),
                'data' => $resolucion,
            ], 422);
        }

        return response()->json([
            'data' => [
                'requires_selection' => (bool) ($resolucion['requiere_seleccion'] ?? false),
                'warehouses' => $this->mapWarehouses((array) ($resolucion['almacenes'] ?? [])),
            ],
        ]);
    }

    public function clients(Request $request): JsonResponse
    {
        $this->mobileUser($request);
        $datos = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
        ]);
        $consulta = trim((string) ($datos['q'] ?? ''));

        if (mb_strlen($consulta) < 2) {
            return response()->json(['data' => []]);
        }

        $clientes = Cliente::query()
            ->where('cli_estatus', 'activo')
            ->where('cli_deleted', false)
            ->whereNull('cli_deleted_at')
            ->where(function ($query) use ($consulta): void {
                $query->where('cli_nombre', 'like', "%{$consulta}%")
                    ->orWhere('cli_apellido_paterno', 'like', "%{$consulta}%")
                    ->orWhere('cli_apellido_materno', 'like', "%{$consulta}%")
                    ->orWhere('cli_razon_social', 'like', "%{$consulta}%")
                    ->orWhere('cli_rfc', 'like', "%{$consulta}%")
                    ->orWhere('cli_curp', 'like', "%{$consulta}%")
                    ->orWhere('cli_email', 'like', "%{$consulta}%")
                    ->orWhere('cli_telefono', 'like', "%{$consulta}%")
                    ->orWhere('cli_whatsapp', 'like', "%{$consulta}%");
            })
            ->orderBy('cli_nombre')
            ->limit(20)
            ->get()
            ->map(function (Cliente $cliente): array {
                $nombre = trim((string) ($cliente->cli_razon_social
                    ?: implode(' ', array_filter([
                        $cliente->cli_nombre,
                        $cliente->cli_apellido_paterno,
                        $cliente->cli_apellido_materno,
                    ]))));
                $detalle = $cliente->cli_rfc
                    ? 'RFC ' . $cliente->cli_rfc
                    : ((string) ($cliente->cli_telefono ?? '') ?: 'Cliente registrado');

                return [
                    'id' => (int) $cliente->cli_id,
                    'name' => $nombre,
                    'detail' => $detalle,
                    'phone' => (string) ($cliente->cli_telefono ?? ''),
                    'email' => (string) ($cliente->cli_email ?? ''),
                    'rfc' => (string) ($cliente->cli_rfc ?? ''),
                    'default_discount' => $cliente->cli_descuento_default !== null
                        ? (int) $cliente->cli_descuento_default
                        : null,
                ];
            })
            ->values();

        return response()->json(['data' => $clientes]);
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

    private function assignedBranchId(Usuario $usuario, int $sucursalId): int
    {
        $asignada = $usuario->sucursales()
            ->where('tbl_sucursales_scl.scl_id', $sucursalId)
            ->where('tbl_sucursales_scl.scl_estatus', 'activo')
            ->exists();

        if (!$asignada) {
            throw ValidationException::withMessages([
                'branch_id' => ['La sucursal seleccionada no está asignada a tu usuario.'],
            ]);
        }

        return $sucursalId;
    }

    private function assignedBranches(Usuario $usuario): array
    {
        return $usuario->sucursales()
            ->where('tbl_sucursales_scl.scl_estatus', 'activo')
            ->select([
                'tbl_sucursales_scl.scl_id',
                'tbl_sucursales_scl.scl_nombre',
                'tbl_sucursales_scl.scl_clave',
            ])
            ->orderByDesc('tbl_usuario_sucursales_usc.usc_es_predeterminada')
            ->orderBy('tbl_sucursales_scl.scl_nombre')
            ->get()
            ->map(fn ($sucursal): array => [
                'id' => (int) $sucursal->scl_id,
                'name' => (string) $sucursal->scl_nombre,
                'code' => (string) $sucursal->scl_clave,
            ])
            ->values()
            ->all();
    }

    private function mapProduct(array $producto, array $resolucion): array
    {
        $unidad = (array) ($producto['producto']['unidad'] ?? []);
        $descripcion = trim((string) ($producto['producto']['prd_descripcion'] ?? ''));
        $almacenes = $this->mapWarehouses((array) ($resolucion['almacenes'] ?? []));

        return [
            'id' => (int) $producto['psk_id'],
            'sku' => (string) ($producto['psk_codigo'] ?? ''),
            'barcode' => (string) ($producto['psk_codigo_barras'] ?? ''),
            'name' => (string) (($producto['psk_nombre'] ?? '') ?: ($producto['producto']['prd_nombre'] ?? 'Producto')),
            'detail' => $descripcion !== '' ? $descripcion : (string) ($producto['producto']['prd_nombre'] ?? ''),
            'price' => round((float) ($producto['psk_precio'] ?? 0), 2),
            'unit' => [
                'code' => (string) ($unidad['umd_codigo'] ?? ''),
                'name' => (string) ($unidad['umd_nombre'] ?? ''),
            ],
            'allows_decimal' => (bool) ($producto['permite_decimal'] ?? false),
            'requires_warehouse_selection' => (bool) ($resolucion['requiere_seleccion'] ?? false),
            'warehouse_id' => !empty($resolucion['almacen_id']) ? (int) $resolucion['almacen_id'] : null,
            'warehouses' => $almacenes,
        ];
    }

    private function mapWarehouses(array $almacenes): array
    {
        return collect($almacenes)
            ->map(fn (array $almacen): array => [
                'id' => (int) ($almacen['alm_id'] ?? 0),
                'name' => (string) ($almacen['alm_nombre'] ?? ''),
            ])
            ->filter(fn (array $almacen): bool => $almacen['id'] > 0)
            ->values()
            ->all();
    }
}
