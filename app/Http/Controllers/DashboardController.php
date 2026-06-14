<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\Permiso;
use App\Models\Sucursal;
use App\Models\Usuario;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard.index');
    }

    public function desktop()
    {
        return view('desktop.dashboard');
    }

    public function desktopUsuarios()
    {
        return view('desktop.usuarios', [
            'opciones' => [
                'roles' => Rol::query()
                    ->where('rol_estatus', 'activo')
                    ->orderBy('rol_nombre')
                    ->get(['rol_id', 'rol_nombre']),
                'sucursales' => Sucursal::query()
                    ->where('scl_estatus', 'activo')
                    ->orderBy('scl_nombre')
                    ->get(['scl_id', 'scl_nombre']),
            ],
        ]);
    }

    public function desktopRoles()
    {
        return view('desktop.roles', [
            'permisos' => Permiso::query()
                ->orderBy('prm_modulo')
                ->orderBy('prm_clave')
                ->get(),
        ]);
    }

    public function desktopPermisos()
    {
        return view('desktop.permisos');
    }

    public function desktopBitacora()
    {
        return view('desktop.bitacora');
    }
}
