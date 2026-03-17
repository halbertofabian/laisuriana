<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Services\BitacoraService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BitacoraController extends Controller
{
    public function __construct(private readonly BitacoraService $bitacoraService)
    {
    }

    public function index()
    {
        return redirect()->route('seguridad.usuarios.index', ['tab' => 'bitacora']);
    }

    public function accesos(Request $request): JsonResponse
    {
        $accesos = $this->bitacoraService->listarAccesos([
            'resultado' => $request->query('resultado'),
            'usuario' => $request->query('usuario'),
            'fecha_desde' => $request->query('fecha_desde'),
            'fecha_hasta' => $request->query('fecha_hasta'),
        ]);

        $data = $accesos->map(function ($item): array {
            $resultado = (string) $item->bac_resultado;

            return [
                'bac_id' => $item->bac_id,
                'usuario_intentado' => $item->bac_usuario_intentado,
                'usuario_registrado' => $item->usuario_registrado,
                'nombre_registrado' => $item->nombre_registrado,
                'resultado' => $resultado,
                'resultado_label' => $resultado === 'exitoso' ? 'Acceso permitido' : 'Acceso denegado',
                'motivo' => $this->traducirMotivoAcceso($item->bac_motivo, $resultado),
                'ip' => $item->bac_ip,
                'fecha' => optional($item->bac_created_at)->format('Y-m-d H:i:s'),
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    public function acciones(Request $request): JsonResponse
    {
        $filtroEvento = trim((string) $request->query('accion', ''));

        $acciones = $this->bitacoraService->listarAcciones([
            'accion' => $filtroEvento,
            'entidad' => $request->query('entidad'),
            'usuario' => $request->query('usuario'),
            'fecha_desde' => $request->query('fecha_desde'),
            'fecha_hasta' => $request->query('fecha_hasta'),
        ]);

        $data = $acciones->map(function ($item): array {
            $evento = $this->traducirAccion((string) $item->bac_accion);
            $detalle = $this->describirQueOcurrio(
                (string) $item->bac_accion,
                (string) ($item->bac_entidad ?? ''),
                (string) ($item->bac_entidad_id ?? ''),
                is_array($item->bac_payload) ? $item->bac_payload : []
            );
            $payload = $this->resumenPayload($item->bac_payload);

            return [
                'bac_id' => $item->bac_id,
                'accion' => $item->bac_accion,
                'evento' => $evento,
                'detalle' => $detalle,
                'usuario' => $item->nombre_registrado ?: $item->usuario_registrado,
                'usuario_login' => $item->usuario_registrado,
                'sucursal' => $item->sucursal_nombre,
                'ip' => $item->bac_ip,
                'payload' => $payload,
                'fecha' => optional($item->bac_created_at)->format('Y-m-d H:i:s'),
            ];
        });

        if ($filtroEvento !== '') {
            $filtroMin = mb_strtolower($filtroEvento);
            $data = $data->filter(function (array $row) use ($filtroMin): bool {
                return str_contains(mb_strtolower((string) $row['evento']), $filtroMin)
                    || str_contains(mb_strtolower((string) $row['detalle']), $filtroMin);
            });
        }

        $data = $data->values();

        return response()->json(['data' => $data]);
    }

    private function traducirMotivoAcceso(?string $motivo, string $resultado): string
    {
        if ($resultado === 'exitoso') {
            return 'Inicio de sesión exitoso.';
        }

        return match ((string) $motivo) {
            'usuario_no_encontrado' => 'No existe un usuario registrado con ese dato.',
            'usuario_inactivo' => 'El usuario está inactivo.',
            'contrasena_incorrecta' => 'La contraseña no coincide.',
            default => 'No fue posible iniciar sesión.',
        };
    }

    private function traducirAccion(string $accion): string
    {
        return match ($accion) {
            'usuario.crear' => 'Alta de usuario',
            'usuario.editar' => 'Edición de usuario',
            'usuario.activar' => 'Activación de usuario',
            'usuario.inactivar' => 'Inactivación de usuario',
            'rol.crear' => 'Alta de rol',
            'rol.editar' => 'Edición de rol',
            'rol.activar' => 'Activación de rol',
            'rol.inactivar' => 'Inactivación de rol',
            'seguridad.cerrar_sesion' => 'Cierre de sesión',
            default => ucwords(str_replace(['.', '_'], ' ', $accion)),
        };
    }

    private function resumenEntidad(string $entidad, string $entidadId): string
    {
        if ($entidad === '') {
            return 'Sin entidad asociada';
        }

        $nombre = match ($entidad) {
            'tbl_usuarios_usr' => 'Usuario',
            'tbl_roles_rol' => 'Rol',
            default => $entidad,
        };

        return trim($nombre . ($entidadId !== '' ? (' #' . $entidadId) : ''));
    }

    private function describirQueOcurrio(string $accion, string $entidad, string $entidadId, array $payload): string
    {
        $usuarioObjetivo = trim((string) ($payload['usr_usuario'] ?? ''));
        $rolObjetivo = trim((string) ($payload['rol_nombre'] ?? ''));

        return match ($accion) {
            'usuario.crear' => 'Se creó un usuario' . ($usuarioObjetivo !== '' ? ": {$usuarioObjetivo}" : ($entidadId !== '' ? " (ID {$entidadId})" : '.')),
            'usuario.editar' => 'Se actualizó un usuario' . ($usuarioObjetivo !== '' ? ": {$usuarioObjetivo}" : ($entidadId !== '' ? " (ID {$entidadId})" : '.')),
            'usuario.activar' => 'Se activó un usuario' . ($usuarioObjetivo !== '' ? ": {$usuarioObjetivo}" : ($entidadId !== '' ? " (ID {$entidadId})" : '.')),
            'usuario.inactivar' => 'Se inactivó un usuario' . ($usuarioObjetivo !== '' ? ": {$usuarioObjetivo}" : ($entidadId !== '' ? " (ID {$entidadId})" : '.')),
            'rol.crear' => 'Se creó un rol' . ($rolObjetivo !== '' ? ": {$rolObjetivo}" : ($entidadId !== '' ? " (ID {$entidadId})" : '.')),
            'rol.editar' => 'Se actualizó un rol' . ($rolObjetivo !== '' ? ": {$rolObjetivo}" : ($entidadId !== '' ? " (ID {$entidadId})" : '.')),
            'rol.activar' => 'Se activó un rol' . ($rolObjetivo !== '' ? ": {$rolObjetivo}" : ($entidadId !== '' ? " (ID {$entidadId})" : '.')),
            'rol.inactivar' => 'Se inactivó un rol' . ($rolObjetivo !== '' ? ": {$rolObjetivo}" : ($entidadId !== '' ? " (ID {$entidadId})" : '.')),
            'seguridad.cerrar_sesion' => 'El usuario cerró su sesión.',
            default => $this->resumenEntidad($entidad, $entidadId),
        };
    }

    private function resumenPayload(mixed $payload): string
    {
        if (!is_array($payload) || $payload === []) {
            return 'Sin detalle adicional';
        }

        $etiquetas = [
            'usr_usuario' => 'Usuario',
            'usr_estatus' => 'Estatus',
            'rol_nombre' => 'Rol',
            'rol_estatus' => 'Estatus rol',
        ];

        $partes = [];
        foreach ($payload as $clave => $valor) {
            $claveLimpia = $etiquetas[$clave] ?? $clave;
            $partes[] = $claveLimpia . ': ' . (is_scalar($valor) ? (string) $valor : json_encode($valor));
        }

        $texto = implode(' | ', $partes);

        return mb_strlen($texto) > 140 ? mb_substr($texto, 0, 140) . '...' : $texto;
    }
}
