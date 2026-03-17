<?php

namespace App\Services;

use App\Models\Permiso;

class PermisoService
{
    public function listar()
    {
        return Permiso::query()
            ->orderBy('prm_modulo')
            ->orderBy('prm_clave')
            ->get();
    }
}
