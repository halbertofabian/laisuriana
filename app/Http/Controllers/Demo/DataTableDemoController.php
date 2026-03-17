<?php

namespace App\Http\Controllers\Demo;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class DataTableDemoController extends Controller
{
    public function index()
    {
        return view('demos.datatables');
    }

    public function data(): JsonResponse
    {
        return response()->json([
            'data' => [
                [
                    'sucursal' => 'Casa Matriz',
                    'almacen' => 'ALM-001',
                    'producto' => 'Tela Gabardina Azul',
                    'existencia' => 125,
                    'estado' => 'Disponible',
                ],
                [
                    'sucursal' => 'Sucursal Centro',
                    'almacen' => 'ALM-003',
                    'producto' => 'Playera Basica Blanca',
                    'existencia' => 60,
                    'estado' => 'Disponible',
                ],
                [
                    'sucursal' => 'Sucursal Norte',
                    'almacen' => 'ALM-002',
                    'producto' => 'Mezclilla Clasica',
                    'existencia' => 12,
                    'estado' => 'Bajo stock',
                ],
                [
                    'sucursal' => 'Sucursal Sur',
                    'almacen' => 'ALM-004',
                    'producto' => 'Forro Satinado Negro',
                    'existencia' => 0,
                    'estado' => 'Sin stock',
                ],
            ],
        ]);
    }
}
