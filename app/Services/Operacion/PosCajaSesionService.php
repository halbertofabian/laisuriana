<?php

namespace App\Services\Operacion;

use App\Models\Caja;
use App\Models\CajaSesion;
use App\Models\CajaSesionUsuario;
use App\Models\Usuario;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosCajaSesionService
{
    public function estadoUsuario(Usuario $usuario): array
    {
        $sesionActiva = $this->sesionActivaDeUsuario($usuario->usr_id);
        $cajasAsignadas = $this->cajasAsignadasUsuario($usuario->usr_id);

        $sesionesDisponibles = CajaSesion::query()
            ->with(['caja:caj_id,caj_nombre,caj_scl_id', 'aperturaUsuario:usr_id,usr_nombre'])
            ->whereIn('cse_caj_id', $cajasAsignadas->pluck('caj_id'))
            ->where('cse_estatus', 'activa')
            ->orderBy('cse_abierta_at', 'desc')
            ->get()
            ->map(fn ($s) => [
                'cse_id' => $s->cse_id,
                'caja_id' => $s->cse_caj_id,
                'caja_nombre' => $s->caja?->caj_nombre,
                'usuario_apertura_id' => $s->cse_usr_apertura_id,
                'usuario_apertura' => $s->aperturaUsuario?->usr_nombre,
                'abierta_at' => optional($s->cse_abierta_at)->format('Y-m-d H:i:s'),
            ])->values();

        $cajasConSesionActivaIds = collect($sesionesDisponibles)
            ->pluck('caja_id')
            ->filter()
            ->unique()
            ->values();

        $cajasParaAbrir = $cajasAsignadas
            ->reject(fn ($c) => $cajasConSesionActivaIds->contains($c->caj_id))
            ->values();

        return [
            'sesion_activa' => $sesionActiva ? $this->mapSesion($sesionActiva) : null,
            'cajas_asignadas' => $cajasAsignadas->map(fn ($c) => [
                'caj_id' => $c->caj_id,
                'caj_nombre' => $c->caj_nombre,
                'sucursal' => $c->sucursal?->scl_nombre,
            ])->values(),
            'cajas_para_abrir' => $cajasParaAbrir->map(fn ($c) => [
                'caj_id' => $c->caj_id,
                'caj_nombre' => $c->caj_nombre,
                'sucursal' => $c->sucursal?->scl_nombre,
            ])->values(),
            'sesiones_disponibles' => $sesionesDisponibles,
        ];
    }

    public function abrirCaja(Usuario $usuario, int $cajaId, float $montoApertura = 0): CajaSesion
    {
        return DB::transaction(function () use ($usuario, $cajaId, $montoApertura): CajaSesion {
            $caja = $this->validarCajaAsignada($usuario->usr_id, $cajaId);

            $existente = $this->sesionActivaDeUsuario($usuario->usr_id);
            if ($existente) {
                return $existente;
            }

            // Si la caja ya tiene sesión activa, el usuario se suma a esa sesión.
            $sesionActivaCaja = CajaSesion::query()
                ->where('cse_caj_id', $caja->caj_id)
                ->where('cse_estatus', 'activa')
                ->orderByDesc('cse_abierta_at')
                ->first();

            if ($sesionActivaCaja) {
                $this->activarParticipacionUsuario($sesionActivaCaja->cse_id, $usuario->usr_id);
                return $sesionActivaCaja->load(['caja', 'aperturaUsuario']);
            }

            $sesion = CajaSesion::query()->create([
                'cse_caj_id' => $caja->caj_id,
                'cse_scl_id' => $caja->caj_scl_id,
                'cse_usr_apertura_id' => $usuario->usr_id,
                'cse_monto_apertura' => $montoApertura,
                'cse_abierta_at' => now(),
                'cse_estatus' => 'activa',
            ]);

            CajaSesionUsuario::query()->create([
                'csu_cse_id' => $sesion->cse_id,
                'csu_usr_id' => $usuario->usr_id,
                'csu_ingreso_at' => now(),
                'csu_estatus' => 'activo',
            ]);

            return $sesion->load(['caja', 'aperturaUsuario']);
        });
    }

    public function tomarCajaAbierta(Usuario $usuario, int $sesionId): CajaSesion
    {
        return DB::transaction(function () use ($usuario, $sesionId): CajaSesion {
            $actual = $this->sesionActivaDeUsuario($usuario->usr_id);
            if ($actual) {
                return $actual;
            }

            $sesion = CajaSesion::query()
                ->where('cse_id', $sesionId)
                ->where('cse_estatus', 'activa')
                ->firstOrFail();

            $this->validarCajaAsignada($usuario->usr_id, $sesion->cse_caj_id);

            $this->activarParticipacionUsuario($sesion->cse_id, $usuario->usr_id);

            return $sesion->load(['caja', 'aperturaUsuario']);
        });
    }

    public function abandonarSesion(Usuario $usuario): void
    {
        DB::transaction(function () use ($usuario): void {
            $sesion = $this->sesionActivaDeUsuario($usuario->usr_id);
            if (!$sesion) {
                return;
            }

            // Evita choque del índice único cuando ya existe un histórico inactivo
            // para la misma sesión/usuario.
            CajaSesionUsuario::query()
                ->where('csu_cse_id', $sesion->cse_id)
                ->where('csu_usr_id', $usuario->usr_id)
                ->where('csu_estatus', 'inactivo')
                ->delete();

            CajaSesionUsuario::query()
                ->where('csu_cse_id', $sesion->cse_id)
                ->where('csu_usr_id', $usuario->usr_id)
                ->where('csu_estatus', 'activo')
                ->whereNull('csu_salida_at')
                ->update([
                    'csu_estatus' => 'inactivo',
                    'csu_salida_at' => now(),
                ]);
        });
    }

    private function activarParticipacionUsuario(int $sesionId, int $usuarioId): void
    {
        $registro = CajaSesionUsuario::query()
            ->where('csu_cse_id', $sesionId)
            ->where('csu_usr_id', $usuarioId)
            ->orderByDesc('csu_id')
            ->first();

        if ($registro) {
            $registro->update([
                'csu_estatus' => 'activo',
                'csu_ingreso_at' => now(),
                'csu_salida_at' => null,
            ]);
            return;
        }

        CajaSesionUsuario::query()->create([
            'csu_cse_id' => $sesionId,
            'csu_usr_id' => $usuarioId,
            'csu_ingreso_at' => now(),
            'csu_estatus' => 'activo',
        ]);
    }

    private function sesionActivaDeUsuario(int $usuarioId): ?CajaSesion
    {
        return CajaSesion::query()
            ->with(['caja:caj_id,caj_nombre,caj_scl_id,caj_alm_id', 'caja.sucursal:scl_id,scl_nombre', 'caja.almacen:alm_id,alm_nombre', 'aperturaUsuario:usr_id,usr_nombre'])
            ->join('tbl_caja_sesion_usuarios_csu as csu', 'csu.csu_cse_id', '=', 'tbl_caja_sesiones_cse.cse_id')
            ->where('csu.csu_usr_id', $usuarioId)
            ->where('csu.csu_estatus', 'activo')
            ->whereNull('csu.csu_salida_at')
            ->where('tbl_caja_sesiones_cse.cse_estatus', 'activa')
            ->select('tbl_caja_sesiones_cse.*')
            ->first();
    }

    private function cajasAsignadasUsuario(int $usuarioId): Collection
    {
        return Caja::query()
            ->with('sucursal:scl_id,scl_nombre')
            ->whereHas('usuarios', fn ($q) => $q->where('usr_id', $usuarioId))
            ->where('caj_estatus', 'activo')
            ->orderBy('caj_nombre')
            ->get();
    }

    private function validarCajaAsignada(int $usuarioId, int $cajaId): Caja
    {
        $caja = Caja::query()
            ->where('caj_id', $cajaId)
            ->where('caj_estatus', 'activo')
            ->whereHas('usuarios', fn ($q) => $q->where('usr_id', $usuarioId))
            ->first();

        if (!$caja) {
            throw ValidationException::withMessages([
                'caja' => 'La caja seleccionada no está disponible para tu usuario.',
            ]);
        }

        return $caja;
    }

    private function mapSesion(CajaSesion $sesion): array
    {
        return [
            'cse_id' => $sesion->cse_id,
            'caja_id' => $sesion->cse_caj_id,
            'caja_scl_id' => $sesion->caja?->caj_scl_id ? (int) $sesion->caja->caj_scl_id : null,
            'caja_nombre' => $sesion->caja?->caj_nombre,
            'caja_alm_id' => $sesion->caja?->caj_alm_id ? (int) $sesion->caja->caj_alm_id : null,
            'caja_almacen' => $sesion->caja?->almacen?->alm_nombre,
            'sucursal' => $sesion->caja?->sucursal?->scl_nombre,
            'usuario_apertura' => $sesion->aperturaUsuario?->usr_nombre,
        ];
    }
}
