<?php

namespace App\Services\Operacion;

use App\Models\CajaSesion;
use App\Models\PosCorteCaja;
use App\Models\PosCorteCajaDenominacion;
use App\Models\Usuario;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PosCorteCajaService
{
    private const DENOMINACIONES_BILLETES = [1000, 500, 200, 100, 50, 20];
    private const DENOMINACIONES_MONEDAS = [
        ['clave' => '10', 'valor' => 10.0],
        ['clave' => '5', 'valor' => 5.0],
        ['clave' => '2', 'valor' => 2.0],
        ['clave' => '1', 'valor' => 1.0],
        ['clave' => '0_50', 'valor' => 0.5],
    ];

    public function __construct(
        private readonly PosCajaSesionService $posCajaSesionService,
        private readonly PosCajaMovimientoService $posCajaMovimientoService,
        private readonly AuditoriaService $auditoriaService,
    ) {
    }

    public function usuariosAutorizados(): Collection
    {
        return Usuario::query()
            ->select('tbl_usuarios_usr.usr_id', 'tbl_usuarios_usr.usr_nombre', 'tbl_usuarios_usr.usr_usuario', 'tbl_usuarios_usr.usr_email')
            ->join('tbl_usuario_roles_url as url', 'url.url_usr_id', '=', 'tbl_usuarios_usr.usr_id')
            ->join('tbl_rol_permisos_rpm as rpm', 'rpm.rpm_rol_id', '=', 'url.url_rol_id')
            ->join('tbl_permisos_prm as prm', 'prm.prm_id', '=', 'rpm.rpm_prm_id')
            ->where('prm.prm_clave', 'pos.corte_caja')
            ->where('tbl_usuarios_usr.usr_estatus', 'activo')
            ->where('tbl_usuarios_usr.usr_deleted', false)
            ->whereNull('tbl_usuarios_usr.usr_deleted_at')
            ->where('url.url_estatus', 'activo')
            ->where('url.url_deleted', false)
            ->whereNull('url.url_deleted_at')
            ->where('rpm.rpm_estatus', 'activo')
            ->where('rpm.rpm_deleted', false)
            ->whereNull('rpm.rpm_deleted_at')
            ->where('prm.prm_estatus', 'activo')
            ->where('prm.prm_deleted', false)
            ->whereNull('prm.prm_deleted_at')
            ->distinct()
            ->orderBy('tbl_usuarios_usr.usr_nombre')
            ->get()
            ->map(fn (Usuario $u) => [
                'usr_id' => (int) $u->usr_id,
                'usr_nombre' => (string) $u->usr_nombre,
                'usr_usuario' => (string) $u->usr_usuario,
                'usr_email' => (string) ($u->usr_email ?? ''),
            ])
            ->values();
    }

    public function cerrar(Request $request, Usuario $cajero, array $datos): PosCorteCaja
    {
        return DB::transaction(function () use ($request, $cajero, $datos): PosCorteCaja {
            $sesion = $this->obtenerSesionActivaBloqueada($cajero);
            $resumen = $this->posCajaMovimientoService->resumenPorSesion((int) $sesion->cse_id);
            $autorizado = $this->validarAutorizacion(
                (int) ($datos['autoriza_usr_id'] ?? 0),
                trim((string) ($datos['autoriza_usuario'] ?? '')),
                (string) ($datos['autoriza_password'] ?? '')
            );

            if (PosCorteCaja::query()
                ->where('pco_cse_id', (int) $sesion->cse_id)
                ->where('pco_deleted', false)
                ->whereNull('pco_deleted_at')
                ->exists()) {
                throw ValidationException::withMessages([
                    'caja' => 'La sesión de caja ya cuenta con un corte registrado.',
                ]);
            }

            $desglose = $this->resolverDenominaciones($datos);
            $efectivoReportado = round((float) collect($desglose)->sum('monto'), 2);
            $efectivoEsperado = round((float) ($resumen['efectivo_disponible'] ?? 0), 2);
            $diferencia = round($efectivoReportado - $efectivoEsperado, 2);
            $folio = $this->crearFolio((int) $sesion->cse_caj_id);
            $cerradoAt = now();

            $corte = PosCorteCaja::query()->create([
                'pco_folio' => $folio,
                'pco_cse_id' => (int) $sesion->cse_id,
                'pco_caj_id' => (int) $sesion->cse_caj_id,
                'pco_scl_id' => (int) $sesion->cse_scl_id,
                'pco_usr_cajero_id' => (int) $cajero->usr_id,
                'pco_usr_autorizo_id' => (int) $autorizado->usr_id,
                'pco_usr_apertura_id' => $sesion->cse_usr_apertura_id ? (int) $sesion->cse_usr_apertura_id : null,
                'pco_abierta_at' => $sesion->cse_abierta_at,
                'pco_cerrada_at' => $cerradoAt,
                'pco_efectivo_esperado' => $efectivoEsperado,
                'pco_efectivo_reportado' => $efectivoReportado,
                'pco_diferencia' => $diferencia,
                'pco_total_ventas' => round((float) ($resumen['total_vendido'] ?? 0), 2),
                'pco_total_retiros' => round((float) ($resumen['retiros'] ?? 0), 2),
                'pco_total_gastos' => round((float) ($resumen['gastos'] ?? 0), 2),
                'pco_resumen_ventas' => [
                    'ventas_del_dia' => (int) ($resumen['ventas_del_dia'] ?? 0),
                    'total_vendido' => round((float) ($resumen['total_vendido'] ?? 0), 2),
                    'efectivo_ventas_neto' => round((float) ($resumen['efectivo_ventas_neto'] ?? 0), 2),
                    'cantidad_cambios' => (int) ($resumen['cantidad_cambios'] ?? 0),
                    'importe_cobrado_cambios' => round((float) ($resumen['importe_cobrado_cambios'] ?? 0), 2),
                    'credito_cambios' => round((float) ($resumen['credito_cambios'] ?? 0), 2),
                ],
                'pco_resumen_metodos_pago' => $resumen['ventas_por_metodo'] ?? [],
                'pco_observaciones' => $datos['observaciones'] ?? null,
                'pco_estado' => 'cerrado',
                'pco_created_by_usr_id' => (int) $cajero->usr_id,
                'pco_updated_by_usr_id' => (int) $cajero->usr_id,
            ]);

            foreach ($desglose as $linea) {
                PosCorteCajaDenominacion::query()->create([
                    'pdn_pco_id' => (int) $corte->pco_id,
                    'pdn_clave' => (string) $linea['clave'],
                    'pdn_etiqueta' => (string) $linea['etiqueta'],
                    'pdn_tipo' => (string) $linea['tipo'],
                    'pdn_cantidad_piezas' => $linea['cantidad_piezas'],
                    'pdn_monto_unitario' => $linea['monto_unitario'],
                    'pdn_monto' => $linea['monto'],
                ]);
            }

            $this->posCajaSesionService->cerrarSesion($sesion, $cerradoAt);

            $this->auditoriaService->registrarAccion(
                $request,
                'pos.caja.corte',
                'tbl_pos_cortes_pco',
                (string) $corte->pco_id,
                [
                    'pco_folio' => $corte->pco_folio,
                    'pco_cse_id' => $corte->pco_cse_id,
                    'pco_efectivo_esperado' => $corte->pco_efectivo_esperado,
                    'pco_efectivo_reportado' => $corte->pco_efectivo_reportado,
                    'pco_diferencia' => $corte->pco_diferencia,
                    'pco_usr_autorizo_id' => $corte->pco_usr_autorizo_id,
                ]
            );

            return $corte->fresh([
                'sesion:cse_id,cse_caj_id,cse_scl_id,cse_usr_apertura_id,cse_monto_apertura,cse_abierta_at,cse_cerrada_at,cse_estatus',
                'caja:caj_id,caj_nombre',
                'cajero:usr_id,usr_nombre,usr_usuario',
                'autorizadoPor:usr_id,usr_nombre,usr_usuario',
                'aperturaUsuario:usr_id,usr_nombre,usr_usuario',
                'denominaciones',
            ]);
        });
    }

    public function crearFolio(int $cajaId): string
    {
        $prefix = 'COR-' . str_pad((string) $cajaId, 3, '0', STR_PAD_LEFT) . '-';
        $last = PosCorteCaja::query()
            ->where('pco_folio', 'like', $prefix . '%')
            ->orderByDesc('pco_id')
            ->value('pco_folio');

        $next = 1;
        if ($last && str_starts_with($last, $prefix)) {
            $next = ((int) substr($last, strlen($prefix))) + 1;
        }

        return $prefix . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    private function obtenerSesionActivaBloqueada(Usuario $usuario): CajaSesion
    {
        $estado = $this->posCajaSesionService->estadoUsuario($usuario);
        $sesion = $estado['sesion_activa'] ?? null;

        if (!$sesion) {
            throw ValidationException::withMessages([
                'caja' => 'No tienes una sesión de caja activa para realizar el corte.',
            ]);
        }

        $sesionModelo = CajaSesion::query()
            ->where('cse_id', (int) $sesion['cse_id'])
            ->where('cse_estatus', 'activa')
            ->lockForUpdate()
            ->first();

        if (!$sesionModelo) {
            throw ValidationException::withMessages([
                'caja' => 'La sesión de caja ya no está disponible para cierre.',
            ]);
        }

        return $sesionModelo;
    }

    private function validarAutorizacion(int $usuarioId, string $usuarioOEmail, string $password): Usuario
    {
        if (($usuarioId <= 0 && $usuarioOEmail === '') || trim($password) === '') {
            throw ValidationException::withMessages([
                'autoriza_usr_id' => 'Captura credenciales válidas para autorizar el corte.',
            ]);
        }

        $usuarioAutorizado = Usuario::query()
            ->where('usr_estatus', 'activo')
            ->where('usr_deleted', false)
            ->whereNull('usr_deleted_at')
            ->where(function ($query) use ($usuarioId, $usuarioOEmail): void {
                if ($usuarioId > 0) {
                    $query->where('usr_id', $usuarioId);
                    return;
                }

                $query->where('usr_usuario', $usuarioOEmail)
                    ->orWhere('usr_email', $usuarioOEmail);
            })
            ->first();

        if (!$usuarioAutorizado || !$usuarioAutorizado->tienePermiso('pos.corte_caja') || !Hash::check($password, (string) $usuarioAutorizado->usr_password)) {
            throw ValidationException::withMessages([
                'autoriza_usr_id' => 'No fue posible validar la autorización del corte con las credenciales proporcionadas.',
            ]);
        }

        return $usuarioAutorizado;
    }

    private function resolverDenominaciones(array $datos): array
    {
        $capturadas = (array) ($datos['denominaciones'] ?? []);
        $desglose = [];

        foreach (self::DENOMINACIONES_BILLETES as $denominacion) {
            $cantidad = (int) ($capturadas[(string) $denominacion] ?? $capturadas[$denominacion] ?? 0);
            $monto = round($cantidad * $denominacion, 2);

            $desglose[] = [
                'clave' => (string) $denominacion,
                'etiqueta' => '$' . $denominacion,
                'tipo' => 'billete',
                'cantidad_piezas' => $cantidad,
                'monto_unitario' => round((float) $denominacion, 2),
                'monto' => $monto,
            ];
        }

        foreach (self::DENOMINACIONES_MONEDAS as $denominacion) {
            $clave = $denominacion['clave'];
            $valor = (float) $denominacion['valor'];
            $cantidad = (int) ($capturadas[$clave] ?? 0);

            $desglose[] = [
                'clave' => (string) $valor,
                'etiqueta' => '$' . number_format($valor, 2),
                'tipo' => 'moneda',
                'cantidad_piezas' => $cantidad,
                'monto_unitario' => $valor,
                'monto' => round($cantidad * $valor, 2),
            ];
        }

        return $desglose;
    }
}
