<?php

namespace App\Services;

use App\Models\BitacoraAcceso;
use App\Models\BitacoraAccion;
use Illuminate\Http\Request;

class AuditoriaService
{
    public function registrarAcceso(Request $request, string $usuarioIntentado, string $resultado, ?string $motivo = null, ?int $usuarioId = null): void
    {
        BitacoraAcceso::query()->create([
            'bac_usr_id' => $usuarioId,
            'bac_usuario_intentado' => $usuarioIntentado,
            'bac_resultado' => $resultado,
            'bac_motivo' => $motivo,
            'bac_ip' => $request->ip(),
            'bac_user_agent' => $request->userAgent(),
        ]);
    }

    public function registrarAccion(Request $request, string $accion, ?string $entidad = null, ?string $entidadId = null, ?array $payload = null): void
    {
        BitacoraAccion::query()->create([
            'bac_usr_id' => optional($request->user())->usr_id,
            'bac_scl_id' => $request->hasSession() ? $request->session()->get('sucursal_activa_id') : null,
            'bac_accion' => $accion,
            'bac_entidad' => $entidad,
            'bac_entidad_id' => $entidadId,
            'bac_payload' => $payload,
            'bac_ip' => $request->ip(),
            'bac_user_agent' => $request->userAgent(),
        ]);
    }
}
