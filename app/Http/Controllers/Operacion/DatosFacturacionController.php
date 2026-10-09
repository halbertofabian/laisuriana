<?php

namespace App\Http\Controllers\Operacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operacion\StoreDatosFacturacionRequest;
use App\Services\Operacion\ClienteService;
use Illuminate\Http\JsonResponse;

class DatosFacturacionController extends Controller
{
    public function store(StoreDatosFacturacionRequest $request, ClienteService $clientes): JsonResponse
    {
        $datos = $request->validated();
        $cliente = $clientes->crear($request, array_merge($datos, [
            'cli_nombre' => mb_substr($datos['cli_razon_social'], 0, 120),
            'cli_estatus' => 'activo',
        ]));

        return response()->json([
            'message' => 'Datos de facturación guardados.',
            'data' => [
                'cli_id' => (int) $cliente->cli_id,
                'nombre' => $cliente->cli_razon_social,
                'rfc' => $cliente->cli_rfc,
                'email' => $cliente->cli_email ?? '',
                'telefono' => $cliente->cli_telefono ?? '',
                'descuento_default' => null,
            ],
        ], 201);
    }
}
