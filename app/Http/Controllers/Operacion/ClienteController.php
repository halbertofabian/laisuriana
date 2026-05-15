<?php

namespace App\Http\Controllers\Operacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operacion\StoreClienteRequest;
use App\Http\Requests\Operacion\UpdateClienteRequest;
use App\Services\Operacion\ClienteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ClienteController extends Controller
{
    public function __construct(private readonly ClienteService $clienteService)
    {
    }

    public function index()
    {
        return view('operacion.clientes.index', [
            'permisosUI' => [
                'cliente_crear' => auth()->user()?->tienePermiso('cliente.crear') ?? false,
                'cliente_editar' => auth()->user()?->tienePermiso('cliente.editar') ?? false,
                'cliente_inactivar' => auth()->user()?->tienePermiso('cliente.inactivar') ?? false,
                'cliente_eliminar' => auth()->user()?->tienePermiso('cliente.eliminar') ?? false,
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
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
                'direccion' => trim(implode(', ', array_filter([$c->cli_colonia, $c->cli_municipio, $c->cli_estado]))),
                'cli_estatus' => $c->cli_estatus,
            ];
        })->values();

        return response()->json(['data' => $clientes]);
    }

    public function show(int $cliente): JsonResponse
    {
        return response()->json([
            'data' => $this->clienteService->obtenerPorId($cliente),
        ]);
    }

    public function store(StoreClienteRequest $request): JsonResponse
    {
        $cliente = $this->clienteService->crear($request, $request->validated());

        return response()->json([
            'message' => 'Cliente creado correctamente.',
            'data' => ['cli_id' => $cliente->cli_id],
        ]);
    }

    public function update(UpdateClienteRequest $request, int $cliente): JsonResponse
    {
        $this->clienteService->actualizar($request, $cliente, $request->validated());

        return response()->json(['message' => 'Cliente actualizado correctamente.']);
    }

    public function cambiarEstatus(Request $request, int $cliente): JsonResponse
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

    public function eliminar(Request $request, int $cliente): JsonResponse
    {
        $this->clienteService->eliminar($request, $cliente);
        return response()->json(['message' => 'Cliente eliminado correctamente.']);
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
}

