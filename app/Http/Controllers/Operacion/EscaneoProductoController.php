<?php

namespace App\Http\Controllers\Operacion;

use App\Http\Controllers\Controller;
use App\Services\Operacion\EscaneoProductoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EscaneoProductoController extends Controller
{
    public function __construct(private readonly EscaneoProductoService $escaneoProductoService)
    {
    }

    public function index()
    {
        return view('operacion.escaneo_productos.index');
    }

    public function buscar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'q' => ['required', 'string', 'max:120'],
            'modo' => ['nullable', 'in:unico,sugerencias'],
        ], [
            'q.required' => 'Debes capturar un código o texto para buscar.',
        ]);

        if (($datos['modo'] ?? 'unico') === 'sugerencias') {
            $sugerencias = $this->escaneoProductoService->sugerencias((string) $datos['q']);

            return response()->json([
                'message' => 'Sugerencias cargadas correctamente.',
                'data' => $sugerencias,
            ]);
        }

        $resultado = $this->escaneoProductoService->buscar((string) $datos['q']);

        if (!$resultado) {
            return response()->json([
                'message' => 'No encontramos coincidencias para la búsqueda enviada.',
            ], 404);
        }

        return response()->json([
            'message' => 'Producto localizado correctamente.',
            'data' => $resultado,
        ]);
    }
}
