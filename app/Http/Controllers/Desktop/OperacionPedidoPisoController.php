<?php

namespace App\Http\Controllers\Desktop;

use App\Http\Controllers\Controller;
use App\Services\Operacion\PedidoPisoService;

class OperacionPedidoPisoController extends Controller
{
    public function __construct(
        private readonly PedidoPisoService $pedidoPisoService,
    ) {
    }

    public function index()
    {
        $opciones = $this->pedidoPisoService->opcionesBase();
        $usuario = auth()->user();
        $defaultSucursalId = $usuario?->sucursales()
            ->orderBy('tbl_sucursales_scl.scl_nombre')
            ->value('tbl_sucursales_scl.scl_id');

        if (!$defaultSucursalId && $opciones['sucursales']->count() === 1) {
            $defaultSucursalId = (int) $opciones['sucursales']->first()->scl_id;
        }

        return view('desktop.operacion.pedido_piso.index', [
            'opciones' => $opciones,
            'defaultSucursalId' => $defaultSucursalId ? (int) $defaultSucursalId : null,
            'usuarioActual' => [
                'usr_id' => (int) ($usuario?->usr_id ?? 0),
                'usr_nombre' => (string) ($usuario?->usr_nombre ?? 'Vendedor'),
            ],
        ]);
    }
}
