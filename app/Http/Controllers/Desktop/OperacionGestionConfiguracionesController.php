<?php

namespace App\Http\Controllers\Desktop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operacion\StoreAlmacenRequest;
use App\Http\Requests\Operacion\StoreCajaRequest;
use App\Http\Requests\Operacion\StoreClienteRequest;
use App\Http\Requests\Operacion\StoreSucursalRequest;
use App\Http\Requests\Operacion\StoreTipoAlmacenRequest;
use App\Http\Requests\Operacion\UpdateAlmacenRequest;
use App\Http\Requests\Operacion\UpdateCajaRequest;
use App\Http\Requests\Operacion\UpdateClienteRequest;
use App\Http\Requests\Operacion\UpdateSucursalRequest;
use App\Http\Requests\Operacion\UpdateTipoAlmacenRequest;
use App\Services\Operacion\AlmacenService;
use App\Services\Operacion\CajaService;
use App\Services\Operacion\ClienteService;
use App\Services\Operacion\SucursalService;
use App\Services\Operacion\TipoAlmacenService;
use App\Models\Almacen;
use App\Models\PosTicketConfiguracion;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class OperacionGestionConfiguracionesController extends Controller
{
    public function __construct(
        private readonly SucursalService $sucursalService,
        private readonly AlmacenService $almacenService,
        private readonly CajaService $cajaService,
        private readonly ClienteService $clienteService,
        private readonly TipoAlmacenService $tipoAlmacenService,
    ) {
    }

    public function index()
    {
        $user = auth()->user();

        if ($user?->tienePermiso('sucursal.ver')) {
            return redirect()->route('desktop.operacion.gestion_configuraciones.sucursales.index');
        }

        if ($user?->tienePermiso('almacen.ver')) {
            return redirect()->route('desktop.operacion.gestion_configuraciones.almacenes.index');
        }

        if ($user?->tienePermiso('tipo_almacen.ver')) {
            return redirect()->route('desktop.operacion.gestion_configuraciones.tipos_almacen.index');
        }

        if ($user?->tienePermiso('caja.ver')) {
            return redirect()->route('desktop.operacion.gestion_configuraciones.cajas.index');
        }

        if ($user?->tienePermiso('cliente.ver')) {
            return redirect()->route('desktop.operacion.gestion_configuraciones.clientes.index');
        }

        abort(403);
    }

    public function sucursales()
    {
        return view('desktop.operacion.gestion_configuraciones.sucursales', [
            'submenus' => $this->submenus(),
            'permisosUI' => [
                'sucursal_crear' => auth()->user()?->tienePermiso('sucursal.crear') ?? false,
                'sucursal_editar' => auth()->user()?->tienePermiso('sucursal.editar') ?? false,
                'sucursal_inactivar' => auth()->user()?->tienePermiso('sucursal.inactivar') ?? false,
                'almacen_ver' => auth()->user()?->tienePermiso('almacen.ver') ?? false,
            ],
        ]);
    }

    public function almacenes()
    {
        return view('desktop.operacion.gestion_configuraciones.almacenes', [
            'submenus' => $this->submenus(),
            'opciones' => [
                'sucursales' => $this->sucursalService->opcionesActivas(),
                'tipos_almacen' => $this->tipoAlmacenService->opcionesActivas(),
            ],
            'permisosUI' => [
                'almacen_crear' => auth()->user()?->tienePermiso('almacen.crear') ?? false,
                'almacen_editar' => auth()->user()?->tienePermiso('almacen.editar') ?? false,
                'almacen_inactivar' => auth()->user()?->tienePermiso('almacen.inactivar') ?? false,
            ],
        ]);
    }

    public function tiposAlmacen()
    {
        return view('desktop.operacion.gestion_configuraciones.tipos_almacen', [
            'submenus' => $this->submenus(),
            'permisosUI' => [
                'tipo_crear' => auth()->user()?->tienePermiso('tipo_almacen.crear') ?? false,
                'tipo_editar' => auth()->user()?->tienePermiso('tipo_almacen.editar') ?? false,
                'tipo_inactivar' => auth()->user()?->tienePermiso('tipo_almacen.inactivar') ?? false,
            ],
        ]);
    }

    public function cajas()
    {
        $usuariosActivos = Usuario::query()
            ->where('usr_estatus', 'activo')
            ->orderBy('usr_nombre')
            ->get(['usr_id', 'usr_nombre', 'usr_usuario']);
        $almacenesActivos = Almacen::query()
            ->where('alm_estatus', 'activo')
            ->orderBy('alm_scl_id')
            ->orderBy('alm_nombre')
            ->get(['alm_id', 'alm_scl_id', 'alm_nombre']);
        $almacenesActivosJs = $almacenesActivos->map(fn ($a) => [
            'alm_id' => (int) $a->alm_id,
            'alm_scl_id' => (int) $a->alm_scl_id,
            'alm_nombre' => (string) $a->alm_nombre,
        ])->values();

        return view('desktop.operacion.gestion_configuraciones.cajas', [
            'submenus' => $this->submenus(),
            'opciones' => [
                'sucursales' => $this->sucursalService->opcionesActivas(),
                'usuarios' => $usuariosActivos,
                'almacenes' => $almacenesActivos,
                'almacenes_js' => $almacenesActivosJs,
            ],
            'permisosUI' => [
                'caja_crear' => auth()->user()?->tienePermiso('caja.crear') ?? false,
                'caja_editar' => auth()->user()?->tienePermiso('caja.editar') ?? false,
                'caja_inactivar' => auth()->user()?->tienePermiso('caja.inactivar') ?? false,
            ],
        ]);
    }

    public function clientes()
    {
        return view('desktop.operacion.gestion_configuraciones.clientes', [
            'submenus' => $this->submenus(),
            'permisosUI' => [
                'cliente_crear' => auth()->user()?->tienePermiso('cliente.crear') ?? false,
                'cliente_editar' => auth()->user()?->tienePermiso('cliente.editar') ?? false,
                'cliente_inactivar' => auth()->user()?->tienePermiso('cliente.inactivar') ?? false,
            ],
        ]);
    }

    public function personalizarTicket()
    {
        $config = PosTicketConfiguracion::singleton();

        return view('desktop.operacion.gestion_configuraciones.ticket', [
            'submenus' => $this->submenus(),
            'config' => $config,
            'logoUrl' => $config->ptc_logo_path ? asset('storage/' . $config->ptc_logo_path) : null,
            'preview' => [
                'empresa' => config('app.name', 'La Suriana'),
                'fecha' => now()->format('d/m/Y H:i'),
                'almacen' => 'I. Suriana',
                'cliente' => 'Cliente de ejemplo',
                'articulos' => 3,
                'vendedores' => 'Maria Lopez, Juan Perez',
                'folio' => 'TCK-000123',
                'items' => [
                    ['nombre' => 'Playera chivas / CH', 'vendedor' => 'Maria Lopez', 'cantidad' => '1', 'importe' => '$500.00'],
                    ['nombre' => 'Playera chivas / G', 'vendedor' => 'Juan Perez', 'cantidad' => '2', 'importe' => '$1,000.00'],
                ],
            ],
            'permisosUI' => [
                'ticket_editar' => auth()->user()?->tienePermiso('caja.editar') ?? false,
            ],
        ]);
    }

    public function dataSucursales(Request $request): JsonResponse
    {
        $sucursales = $this->sucursalService->listar([
            'buscar' => $request->query('buscar'),
            'estatus' => $request->query('estatus'),
        ]);

        $data = $sucursales->map(function ($sucursal): array {
            return [
                'scl_id' => $sucursal->scl_id,
                'scl_nombre' => $sucursal->scl_nombre,
                'scl_clave' => $sucursal->scl_clave,
                'scl_estatus' => $sucursal->scl_estatus,
                'almacenes_total' => $sucursal->almacenes_total,
                'almacenes_activos' => $sucursal->almacenes_activos,
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    public function showSucursal(int $sucursal): JsonResponse
    {
        $registro = $this->sucursalService
            ->listar()
            ->firstWhere('scl_id', $sucursal);

        abort_if(!$registro, 404);

        return response()->json([
            'data' => [
                'scl_id' => $registro->scl_id,
                'scl_nombre' => $registro->scl_nombre,
                'scl_clave' => $registro->scl_clave,
                'scl_estatus' => $registro->scl_estatus,
                'almacenes_total' => $registro->almacenes_total,
                'almacenes_activos' => $registro->almacenes_activos,
            ],
        ]);
    }

    public function storeSucursal(StoreSucursalRequest $request): JsonResponse
    {
        $sucursal = $this->sucursalService->crear($request, $request->validated());

        return response()->json([
            'message' => 'Sucursal creada correctamente.',
            'data' => ['scl_id' => $sucursal->scl_id],
        ]);
    }

    public function updateSucursal(UpdateSucursalRequest $request, int $sucursal): JsonResponse
    {
        $this->sucursalService->actualizar($request, $sucursal, $request->validated());

        return response()->json([
            'message' => 'Sucursal actualizada correctamente.',
        ]);
    }

    public function cambiarEstatusSucursal(Request $request, int $sucursal): JsonResponse
    {
        $request->validate([
            'scl_estatus' => ['required', 'in:activo,inactivo'],
        ], [
            'scl_estatus.required' => 'El estatus es obligatorio.',
            'scl_estatus.in' => 'El estatus enviado no es válido.',
        ]);

        $registro = $this->sucursalService->cambiarEstatus(
            $request,
            $sucursal,
            $request->string('scl_estatus')->toString()
        );

        return response()->json([
            'message' => 'Estatus de sucursal actualizado correctamente.',
            'data' => ['scl_estatus' => $registro->scl_estatus],
        ]);
    }

    public function dataAlmacenes(Request $request): JsonResponse
    {
        $almacenes = $this->almacenService->listar([
            'buscar' => $request->query('buscar'),
            'estatus' => $request->query('estatus'),
            'alm_scl_id' => $request->query('alm_scl_id'),
            'alm_tal_id' => $request->query('alm_tal_id'),
        ]);

        $data = $almacenes->map(function ($almacen): array {
            return [
                'alm_id' => $almacen->alm_id,
                'alm_nombre' => $almacen->alm_nombre,
                'alm_clave' => $almacen->alm_clave,
                'alm_estatus' => $almacen->alm_estatus,
                'alm_scl_id' => $almacen->alm_scl_id,
                'alm_tal_id' => $almacen->alm_tal_id,
                'sucursal' => $almacen->sucursal?->scl_nombre,
                'tipo' => $almacen->tipo?->tal_nombre,
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    public function showAlmacen(int $almacen): JsonResponse
    {
        $registro = $this->almacenService->obtenerPorId($almacen);

        return response()->json([
            'data' => [
                'alm_id' => $registro->alm_id,
                'alm_scl_id' => $registro->alm_scl_id,
                'alm_tal_id' => $registro->alm_tal_id,
                'alm_nombre' => $registro->alm_nombre,
                'alm_clave' => $registro->alm_clave,
                'alm_estatus' => $registro->alm_estatus,
            ],
        ]);
    }

    public function storeAlmacen(StoreAlmacenRequest $request): JsonResponse
    {
        $almacen = $this->almacenService->crear($request, $request->validated());

        return response()->json([
            'message' => 'Almacén creado correctamente.',
            'data' => ['alm_id' => $almacen->alm_id],
        ]);
    }

    public function updateAlmacen(UpdateAlmacenRequest $request, int $almacen): JsonResponse
    {
        $this->almacenService->actualizar($request, $almacen, $request->validated());

        return response()->json([
            'message' => 'Almacén actualizado correctamente.',
        ]);
    }

    public function cambiarEstatusAlmacen(Request $request, int $almacen): JsonResponse
    {
        $request->validate([
            'alm_estatus' => ['required', 'in:activo,inactivo'],
        ], [
            'alm_estatus.required' => 'El estatus es obligatorio.',
            'alm_estatus.in' => 'El estatus enviado no es válido.',
        ]);

        $registro = $this->almacenService->cambiarEstatus(
            $request,
            $almacen,
            $request->string('alm_estatus')->toString()
        );

        return response()->json([
            'message' => 'Estatus de almacén actualizado correctamente.',
            'data' => ['alm_estatus' => $registro->alm_estatus],
        ]);
    }

    public function dataTiposAlmacen(Request $request): JsonResponse
    {
        $tipos = $this->tipoAlmacenService->listar([
            'buscar' => $request->query('buscar'),
            'estatus' => $request->query('estatus'),
        ]);

        $data = $tipos->map(function ($tipo): array {
            return [
                'tal_id' => $tipo->tal_id,
                'tal_nombre' => $tipo->tal_nombre,
                'tal_clave' => $tipo->tal_clave,
                'tal_descripcion' => $tipo->tal_descripcion,
                'tal_estatus' => $tipo->tal_estatus,
                'almacenes_total' => $tipo->almacenes_total,
                'almacenes_activos' => $tipo->almacenes_activos,
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    public function showTipoAlmacen(int $tipo_almacen): JsonResponse
    {
        $registro = $this->tipoAlmacenService->obtenerPorId($tipo_almacen);

        return response()->json([
            'data' => [
                'tal_id' => $registro->tal_id,
                'tal_nombre' => $registro->tal_nombre,
                'tal_clave' => $registro->tal_clave,
                'tal_descripcion' => $registro->tal_descripcion,
                'tal_estatus' => $registro->tal_estatus,
            ],
        ]);
    }

    public function storeTipoAlmacen(StoreTipoAlmacenRequest $request): JsonResponse
    {
        $tipo = $this->tipoAlmacenService->crear($request, $request->validated());

        return response()->json([
            'message' => 'Tipo de almacén creado correctamente.',
            'data' => ['tal_id' => $tipo->tal_id],
        ]);
    }

    public function updateTipoAlmacen(UpdateTipoAlmacenRequest $request, int $tipo_almacen): JsonResponse
    {
        $this->tipoAlmacenService->actualizar($request, $tipo_almacen, $request->validated());

        return response()->json([
            'message' => 'Tipo de almacén actualizado correctamente.',
        ]);
    }

    public function cambiarEstatusTipoAlmacen(Request $request, int $tipo_almacen): JsonResponse
    {
        $request->validate([
            'tal_estatus' => ['required', 'in:activo,inactivo'],
        ], [
            'tal_estatus.required' => 'El estatus es obligatorio.',
            'tal_estatus.in' => 'El estatus enviado no es válido.',
        ]);

        $registro = $this->tipoAlmacenService->cambiarEstatus(
            $request,
            $tipo_almacen,
            $request->string('tal_estatus')->toString()
        );

        return response()->json([
            'message' => 'Estatus de tipo de almacén actualizado correctamente.',
            'data' => ['tal_estatus' => $registro->tal_estatus],
        ]);
    }

    public function dataCajas(Request $request): JsonResponse
    {
        $cajas = $this->cajaService->listar([
            'buscar' => $request->query('buscar'),
            'estatus' => $request->query('estatus'),
            'caj_scl_id' => $request->query('caj_scl_id'),
        ]);

        $data = $cajas->map(function ($caja): array {
            return [
                'caj_id' => $caja->caj_id,
                'caj_nombre' => $caja->caj_nombre,
                'caj_clave' => $caja->caj_clave,
                'caj_estatus' => $caja->caj_estatus,
                'caj_scl_id' => $caja->caj_scl_id,
                'caj_alm_id' => $caja->caj_alm_id,
                'sucursal' => $caja->sucursal?->scl_nombre,
                'almacen' => $caja->almacen?->alm_nombre,
                'usuarios' => $caja->usuarios->map(fn ($u) => [
                    'usr_id' => $u->usr_id,
                    'usr_nombre' => $u->usr_nombre,
                    'usr_usuario' => $u->usr_usuario,
                ])->values(),
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    public function showCaja(int $caja): JsonResponse
    {
        $registro = $this->cajaService->obtenerPorId($caja);

        return response()->json([
            'data' => [
                'caj_id' => $registro->caj_id,
                'caj_scl_id' => $registro->caj_scl_id,
                'caj_nombre' => $registro->caj_nombre,
                'caj_clave' => $registro->caj_clave,
                'caj_estatus' => $registro->caj_estatus,
                'caj_alm_id' => $registro->caj_alm_id,
                'usuarios' => $registro->usuarios->pluck('usr_id')->values(),
            ],
        ]);
    }

    public function storeCaja(StoreCajaRequest $request): JsonResponse
    {
        $caja = $this->cajaService->crear($request, $request->validated());

        return response()->json([
            'message' => 'Caja creada correctamente.',
            'data' => ['caj_id' => $caja->caj_id],
        ]);
    }

    public function updateCaja(UpdateCajaRequest $request, int $caja): JsonResponse
    {
        $this->cajaService->actualizar($request, $caja, $request->validated());

        return response()->json([
            'message' => 'Caja actualizada correctamente.',
        ]);
    }

    public function cambiarEstatusCaja(Request $request, int $caja): JsonResponse
    {
        $request->validate([
            'caj_estatus' => ['required', 'in:activo,inactivo'],
        ], [
            'caj_estatus.required' => 'El estatus es obligatorio.',
            'caj_estatus.in' => 'El estatus enviado no es válido.',
        ]);

        $registro = $this->cajaService->cambiarEstatus(
            $request,
            $caja,
            $request->string('caj_estatus')->toString()
        );

        return response()->json([
            'message' => 'Estatus de caja actualizado correctamente.',
            'data' => ['caj_estatus' => $registro->caj_estatus],
        ]);
    }

    public function dataClientes(Request $request): JsonResponse
    {
        $clientes = $this->clienteService->listar([
            'buscar' => $request->query('buscar'),
            'estatus' => $request->query('estatus'),
        ])->map(function ($c): array {
            return [
                'cli_id' => $c->cli_id,
                'nombre_completo' => trim(implode(' ', array_filter([$c->cli_nombre, $c->cli_apellido_paterno, $c->cli_apellido_materno]))),
                'cli_razon_social' => $c->cli_razon_social,
                'cli_telefono' => $c->cli_telefono,
                'cli_whatsapp' => $c->cli_whatsapp,
                'cli_email' => $c->cli_email,
                'cli_rfc' => $c->cli_rfc,
                'cli_curp' => $c->cli_curp,
                'cli_descuento_default' => $c->cli_descuento_default,
                'direccion' => trim(implode(', ', array_filter([$c->cli_colonia, $c->cli_municipio, $c->cli_estado]))),
                'cli_estatus' => $c->cli_estatus,
            ];
        })->values();

        return response()->json(['data' => $clientes]);
    }

    public function showCliente(int $cliente): JsonResponse
    {
        return response()->json([
            'data' => $this->clienteService->obtenerPorId($cliente),
        ]);
    }

    public function storeCliente(StoreClienteRequest $request): JsonResponse
    {
        $cliente = $this->clienteService->crear($request, $request->validated());

        return response()->json([
            'message' => 'Cliente creado correctamente.',
            'data' => ['cli_id' => $cliente->cli_id],
        ]);
    }

    public function updateCliente(UpdateClienteRequest $request, int $cliente): JsonResponse
    {
        $this->clienteService->actualizar($request, $cliente, $request->validated());

        return response()->json(['message' => 'Cliente actualizado correctamente.']);
    }

    public function cambiarEstatusCliente(Request $request, int $cliente): JsonResponse
    {
        $request->validate([
            'cli_estatus' => ['required', 'in:activo,inactivo'],
        ]);

        $registro = $this->clienteService->cambiarEstatus($request, $cliente, $request->string('cli_estatus')->toString());

        return response()->json([
            'message' => 'Estatus actualizado correctamente.',
            'data' => ['cli_estatus' => $registro->cli_estatus],
        ]);
    }

    public function buscarCodigoPostal(Request $request): JsonResponse
    {
        $data = $request->validate([
            'codigo_postal' => ['required', 'string', 'max:10'],
        ]);

        $url = 'https://app.tallercontrol.com/api/public/codigos_postales/codigo/' . urlencode($data['codigo_postal']);
        $response = Http::timeout(8)->acceptJson()->get($url);

        if (!$response->ok()) {
            return response()->json(['message' => 'No fue posible consultar el código postal.'], 422);
        }

        $rows = collect($response->json())
            ->filter(fn ($r) => is_array($r))
            ->map(fn ($r) => [
                'cp_codigo' => (string) ($r['cp_codigo'] ?? ''),
                'cp_asentamiento' => (string) ($r['cp_asentamiento'] ?? ''),
                'cp_tipo_asentamiento' => (string) ($r['cp_tipo_asentamiento'] ?? ''),
                'cp_municipio' => (string) ($r['cp_municipio'] ?? ''),
                'cp_estado' => (string) ($r['cp_estado'] ?? ''),
                'cp_ciudad' => (string) ($r['cp_ciudad'] ?? ''),
            ])
            ->values();

        return response()->json(['data' => $rows]);
    }

    public function updatePersonalizarTicket(Request $request)
    {
        $data = $request->validate([
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'eliminar_logo' => ['nullable', 'boolean'],
            'texto_encabezado' => ['nullable', 'string', 'max:4000'],
            'texto_pie' => ['nullable', 'string', 'max:4000'],
        ]);

        $config = PosTicketConfiguracion::singleton();
        $payload = [
            'ptc_texto_encabezado' => trim((string) ($data['texto_encabezado'] ?? '')) ?: null,
            'ptc_texto_pie' => trim((string) ($data['texto_pie'] ?? '')) ?: null,
            'ptc_updated_by_usr_id' => $request->user()?->usr_id,
        ];

        if (!$config->ptc_created_by_usr_id) {
            $payload['ptc_created_by_usr_id'] = $request->user()?->usr_id;
        }

        $removeLogo = (bool) ($data['eliminar_logo'] ?? false);

        if ($removeLogo && $config->ptc_logo_path) {
            Storage::disk('public')->delete($config->ptc_logo_path);
            $payload['ptc_logo_path'] = null;
        }

        if ($request->hasFile('logo')) {
            if ($config->ptc_logo_path) {
                Storage::disk('public')->delete($config->ptc_logo_path);
            }

            $payload['ptc_logo_path'] = $request->file('logo')->store('tickets/personalizacion', 'public');
        }

        $config->fill($payload);
        $config->save();

        return redirect()
            ->route('desktop.operacion.gestion_configuraciones.ticket.index')
            ->with('success', 'Personalizacion del ticket actualizada correctamente.');
    }

    private function renderPlaceholder(string $active, string $title, string $description)
    {
        return view('desktop.operacion.gestion_configuraciones.placeholder', [
            'activeSubmenu' => $active,
            'pageTitle' => $title,
            'pageDescription' => $description,
            'submenus' => $this->submenus(),
        ]);
    }

    private function submenus(): array
    {
        return [
            ['key' => 'sucursales', 'label' => 'Sucursales', 'route' => route('desktop.operacion.gestion_configuraciones.sucursales.index')],
            ['key' => 'almacenes', 'label' => 'Almacenes', 'route' => route('desktop.operacion.gestion_configuraciones.almacenes.index')],
            ['key' => 'tipos_almacen', 'label' => 'Tipos de almacén', 'route' => route('desktop.operacion.gestion_configuraciones.tipos_almacen.index')],
            ['key' => 'cajas', 'label' => 'Cajas', 'route' => route('desktop.operacion.gestion_configuraciones.cajas.index')],
            ['key' => 'clientes', 'label' => 'Clientes', 'route' => route('desktop.operacion.gestion_configuraciones.clientes.index')],
            ['key' => 'ticket', 'label' => 'Personalizar ticket', 'route' => route('desktop.operacion.gestion_configuraciones.ticket.index')],
        ];
    }
}
