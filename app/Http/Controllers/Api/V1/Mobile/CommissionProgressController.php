<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Services\Reportes\ComisionV2Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommissionProgressController extends Controller
{
    public function __invoke(Request $request, ComisionV2Service $comisiones): JsonResponse
    {
        $datos = $request->validate(['branch_id' => ['required', 'integer']]);
        $sucursalId = (int) $datos['branch_id'];
        abort_unless($request->user()->tienePermiso('comisiones.avance.propio'), 403, 'No tienes permiso para consultar el avance de comisión.');
        abort_unless($request->user()->sucursales()->where('tbl_sucursales_scl.scl_id', $sucursalId)->exists(), 403, 'No tienes acceso a la sucursal seleccionada.');

        return response()->json(['data' => $comisiones->avancePropio((int) $request->user()->usr_id, $sucursalId)]);
    }
}
