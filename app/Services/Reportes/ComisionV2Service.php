<?php

namespace App\Services\Reportes;

use App\Models\ComisionV2Departamento;
use App\Models\ComisionV2Participante;
use App\Models\ComisionV2Periodo;
use App\Models\ComisionV2PeriodoDepartamento;
use App\Models\ComisionV2Resultado;
use App\Models\ComisionV2ResultadoDetalle;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ComisionV2Service
{
    public const TASA_ORDINARIA = 0.9;

    public const FACTOR_COMISIONABLE = 33;

    public function __construct(
        private readonly ComisionV2MovimientoService $movimientos,
        private readonly ?ComisionHistoricoService $historico = null,
    ) {}

    public function guardar(array $datos, int $sucursalId, int $usuarioId): ComisionV2Periodo
    {
        return DB::transaction(function () use ($datos, $sucursalId, $usuarioId): ComisionV2Periodo {
            $fecha = Carbon::createFromFormat('Y-m', $datos['periodo'])->startOfMonth();
            $periodo = ComisionV2Periodo::query()
                ->where('cmp_scl_id', $sucursalId)
                ->whereDate('cmp_periodo', $fecha->toDateString())
                ->lockForUpdate()
                ->first();

            if ($periodo?->cmp_estatus === 'cerrado') {
                throw ValidationException::withMessages(['periodo' => 'El periodo está cerrado y no admite cambios.']);
            }
            if ($periodo?->cmp_estatus === 'aprobado' && trim((string) ($datos['motivo_cambio'] ?? '')) === '') {
                throw ValidationException::withMessages(['motivo_cambio' => 'Explica por qué se modifica un periodo ya aprobado.']);
            }

            $almacenes = DB::table('tbl_almacenes_alm')
                ->whereIn('alm_id', $datos['almacen_ids'])
                ->where('alm_scl_id', $sucursalId)
                ->where('alm_estatus', 'activo')
                ->where('alm_deleted', false)
                ->pluck('alm_id')->map(fn ($id) => (int) $id)->all();
            if (count($almacenes) !== count(array_unique(array_map('intval', $datos['almacen_ids'])))) {
                throw ValidationException::withMessages(['almacen_ids' => 'Los almacenes deben estar activos y pertenecer a la sucursal actual.']);
            }

            $periodo ??= new ComisionV2Periodo([
                'cmp_scl_id' => $sucursalId,
                'cmp_periodo' => $fecha,
                'cmp_created_by_usr_id' => $usuarioId,
                'cmp_estatus' => 'borrador',
            ]);
            $periodo->fill([
                'cmp_factor_comisionable' => self::FACTOR_COMISIONABLE,
                'cmp_ultimo_motivo_cambio' => trim((string) ($datos['motivo_cambio'] ?? '')) ?: null,
                'cmp_updated_by_usr_id' => $usuarioId,
            ])->save();
            $periodo->almacenes()->sync($almacenes);

            $periodo->participantes()->delete();
            DB::table('tbl_comision_v2_periodo_lineas_cml')->where('cml_cmp_id', $periodo->cmp_id)->delete();
            $periodo->departamentos()->delete();

            $departamentos = ComisionV2Departamento::query()->where('cmd_estatus', 'activo')->get()->keyBy('cmd_id');
            $configuraciones = [];
            foreach ((array) $datos['departamentos'] as $departamentoId => $config) {
                if (! ($config['habilitado'] ?? false)) {
                    continue;
                }
                $departamento = $departamentos->get((int) $departamentoId);
                if (! $departamento) {
                    continue;
                }
                $modo = $this->modoMeta($config);
                $configPeriodo = ComisionV2PeriodoDepartamento::query()->create([
                    'cpd_cmp_id' => $periodo->cmp_id,
                    'cpd_cmd_id' => $departamento->cmd_id,
                    'cpd_departamento_nombre' => $departamento->cmd_nombre,
                    'cpd_origen_meta' => $modo,
                    'cpd_vendedores_congelados' => 0,
                    'cpd_incremento_meta' => (float) ($config['incremento_meta'] ?? 0),
                    'cpd_meta_comun' => $modo === 'manual' ? $this->importePositivo($config['meta_comun'] ?? null) : null,
                ]);
                $configuraciones[(int) $departamentoId] = $configPeriodo;
                $lineaIds = collect($config['linea_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values()->all();
                foreach ($lineaIds as $lineaId) {
                    DB::table('tbl_comision_v2_periodo_lineas_cml')->insert([
                        'cml_cmp_id' => $periodo->cmp_id,
                        'cml_cpd_id' => $configPeriodo->cpd_id,
                        'cml_lna_id' => $lineaId,
                        'cml_linea_nombre' => (string) DB::table('tbl_lineas_lna')->where('lna_id', $lineaId)->value('lna_nombre'),
                        'cml_created_at' => now(),
                        'cml_updated_at' => now(),
                    ]);
                }
            }

            $usuariosValidos = DB::table('tbl_usuario_sucursales_usc as usc')
                ->join('tbl_usuarios_usr as usr', 'usr.usr_id', '=', 'usc.usc_usr_id')
                ->where('usc.usc_scl_id', $sucursalId)
                ->where('usc.usc_estatus', 'activo')->where('usc.usc_deleted', false)->whereNull('usc.usc_deleted_at')
                ->where('usr.usr_estatus', 'activo')->where('usr.usr_deleted', false)
                ->get(['usr.usr_id', 'usr.usr_nombre'])->keyBy('usr_id');
            $equipo = [];
            foreach ((array) ($datos['vendedores'] ?? []) as $usuarioFilaId => $fila) {
                if (! ($fila['habilitado'] ?? false)) {
                    continue;
                }
                $usuario = $usuariosValidos->get((int) $usuarioFilaId);
                $configPeriodo = $configuraciones[(int) ($fila['departamento_id'] ?? 0)] ?? null;
                if (! $usuario || ! $configPeriodo) {
                    throw ValidationException::withMessages(['vendedores' => 'Todos los vendedores deben estar activos, pertenecer a la sucursal y tener departamento.']);
                }
                $equipo[(int) $usuarioFilaId] = [$fila, $usuario, $configPeriodo];
            }

            $periodo->load('almacenes');
            $historicos = $this->movimientos->obtener(
                $periodo,
                $fecha->copy()->subYear()->startOfMonth(),
                $fecha->copy()->subYear()->endOfMonth(),
            );
            $referencias = ($this->historico ?? app(ComisionHistoricoService::class))
                ->resumirReferencia($periodo, $historicos, $fecha->copy()->subYear());
            $metasComunes = [];
            foreach ($configuraciones as $configPeriodo) {
                $referencia = $referencias->get($configPeriodo->cpd_id);
                $cantidad = collect($equipo)->filter(fn ($fila) => $fila[2]->cpd_id === $configPeriodo->cpd_id)->count();
                $ventas = (float) ($referencia['ventas'] ?? 0);
                $autoservicio = (float) ($referencia['autoservicio'] ?? 0);
                $base = round($ventas - $autoservicio, 2);
                $sugerida = $this->metaSugerida($base, $cantidad, (float) $configPeriodo->cpd_incremento_meta);
                $metaComun = $this->resolverMetaComun(
                    $configPeriodo->cpd_origen_meta,
                    $configPeriodo->cpd_meta_comun !== null ? (float) $configPeriodo->cpd_meta_comun : null,
                    $sugerida,
                );
                $metasComunes[$configPeriodo->cpd_id] = $metaComun;
                $configPeriodo->update([
                    'cpd_periodo_referencia' => $base > 0 ? $fecha->copy()->subYear() : null,
                    'cpd_ventas_historicas' => $ventas,
                    'cpd_autoservicio_historico' => $autoservicio,
                    'cpd_base_historica' => $base,
                    'cpd_vendedores_congelados' => $cantidad,
                    'cpd_meta_sugerida' => $sugerida,
                    'cpd_meta_comun' => $metaComun,
                ]);
            }

            $errores = [];
            foreach ($equipo as $usuarioFilaId => [$fila, $usuario, $configPeriodo]) {
                $metaComun = $metasComunes[$configPeriodo->cpd_id];
                $metaIndividual = (float) ($fila['meta'] ?? 0);
                $tasa = (float) ($fila['tasa'] ?? self::TASA_ORDINARIA);
                $motivo = trim((string) ($fila['motivo'] ?? '')) ?: null;
                if ($motivo === null && $this->requiereMotivoAjuste($metaIndividual, $metaComun, $tasa)) {
                    $errores["vendedores.$usuarioFilaId.motivo"] = "Explica el ajuste de meta o tasa de {$usuario->usr_nombre}.";
                }
                ComisionV2Participante::query()->create([
                    'cpt_cmp_id' => $periodo->cmp_id,
                    'cpt_cpd_id' => $configPeriodo->cpd_id,
                    'cpt_usr_id' => $usuarioFilaId,
                    'cpt_numero_vendedor' => trim((string) $fila['numero']),
                    'cpt_nombre_vendedor' => $usuario->usr_nombre,
                    'cpt_meta_individual' => $metaIndividual > 0 ? $metaIndividual : ($metaComun ?? 0),
                    'cpt_tasa_comision' => $tasa,
                    'cpt_motivo_ajuste' => $motivo,
                ]);
            }
            if ($errores !== []) {
                throw ValidationException::withMessages($errores);
            }

            return $periodo->fresh(['almacenes', 'departamentos.participantes', 'participantes']);
        });
    }

    /**
     * Calcula, sin guardar, la referencia histórica y las metas que produciría la selección.
     * Usa las mismas reglas que guardar() para que la vista previa no diverja del resultado.
     */
    public function vistaPrevia(array $datos, int $sucursalId): array
    {
        $fecha = Carbon::createFromFormat('Y-m', $datos['periodo'])->startOfMonth();
        $mesReferencia = $fecha->copy()->subYear();
        $almacenIds = DB::table('tbl_almacenes_alm')
            ->whereIn('alm_id', array_map('intval', (array) ($datos['almacen_ids'] ?? [])))
            ->where('alm_scl_id', $sucursalId)
            ->where('alm_estatus', 'activo')
            ->where('alm_deleted', false)
            ->pluck('alm_id')->map(fn ($id) => (int) $id)->all();
        $catalogo = ComisionV2Departamento::query()->where('cmd_estatus', 'activo')->get()->keyBy('cmd_id');
        $guardado = ComisionV2Periodo::query()
            ->with(['departamentos', 'participantes'])
            ->where('cmp_scl_id', $sucursalId)
            ->whereDate('cmp_periodo', $fecha->toDateString())
            ->first();

        $departamentos = [];
        $lineaGrupos = [];
        $lineasRepetidas = [];
        foreach ((array) ($datos['departamentos'] ?? []) as $departamentoId => $config) {
            if (! ($config['habilitado'] ?? false) || ! $catalogo->has((int) $departamentoId)) {
                continue;
            }
            foreach ((array) ($config['linea_ids'] ?? []) as $lineaId) {
                if (isset($lineaGrupos[(int) $lineaId])) {
                    $lineasRepetidas[] = (int) $lineaId;
                    continue;
                }
                $lineaGrupos[(int) $lineaId] = (int) $departamentoId;
            }
            $departamentos[(int) $departamentoId] = $config;
        }

        $movimientos = $this->movimientos->obtenerPorLineas(
            $sucursalId,
            $almacenIds,
            $lineaGrupos,
            $mesReferencia->copy()->startOfMonth(),
            $mesReferencia->copy()->endOfMonth(),
        );
        $referencias = ($this->historico ?? app(ComisionHistoricoService::class))
            ->referencia($sucursalId, $mesReferencia, $almacenIds, $lineaGrupos, $movimientos);
        $vendedores = collect((array) ($datos['vendedores'] ?? []))->filter(fn ($fila) => (bool) ($fila['habilitado'] ?? false));

        $resultado = [];
        foreach ($departamentos as $departamentoId => $config) {
            $referencia = $referencias->get($departamentoId);
            $equipo = $vendedores->filter(fn ($fila) => (int) ($fila['departamento_id'] ?? 0) === $departamentoId);
            $cantidad = $equipo->count();
            $modo = $this->modoMeta($config);
            $incremento = (float) ($config['incremento_meta'] ?? 0);
            $base = (float) ($referencia['base'] ?? 0);
            $sugerida = $this->metaSugerida($base, $cantidad, $incremento);
            $metaManual = $this->importePositivo($config['meta_comun'] ?? null);
            $metaComun = $this->resolverMetaComun($modo, $metaManual, $sugerida);
            $estado = match (true) {
                $referencia === null || $almacenIds === [] => 'sin_alcance',
                $base <= 0 => 'sin_base',
                $referencia['faltantes'] > 0 => 'parcial',
                default => 'completa',
            };
            $falta = match (true) {
                $modo === 'manual' => $metaManual === null ? 'meta_manual' : null,
                $estado === 'sin_alcance' => 'alcance',
                $estado === 'sin_base' => 'base',
                $cantidad === 0 => 'vendedores',
                default => null,
            };
            $configGuardada = $guardado?->departamentos->firstWhere('cpd_cmd_id', $departamentoId);
            $resultado[$departamentoId] = [
                'estado' => $estado,
                'origen' => $referencia['origen'] ?? null,
                'ventas' => (float) ($referencia['ventas'] ?? 0),
                'autoservicio' => (float) ($referencia['autoservicio'] ?? 0),
                'base' => $base,
                'ventas_sistema' => (float) ($referencia['ventas_sistema'] ?? 0),
                'ventas_manual' => (float) ($referencia['ventas_manual'] ?? 0),
                'combinaciones' => $referencia['combinaciones'] ?? [],
                'faltantes' => (int) ($referencia['faltantes'] ?? 0),
                'vendedores' => $cantidad,
                'promedio' => $base > 0 && $cantidad > 0 ? round($base / $cantidad, 2) : null,
                'modo' => $modo,
                'incremento' => $incremento,
                'sugerida' => $sugerida,
                'meta_manual' => $metaManual,
                'meta_comun' => $metaComun,
                'falta' => $falta,
                'ajustes' => $equipo->filter(fn ($fila) => (float) ($fila['meta'] ?? 0) > 0)->count(),
                'guardado' => $configGuardada ? [
                    'meta_comun' => $configGuardada->cpd_meta_comun !== null ? (float) $configGuardada->cpd_meta_comun : null,
                    'meta_sugerida' => $configGuardada->cpd_meta_sugerida !== null ? (float) $configGuardada->cpd_meta_sugerida : null,
                    'modo' => $configGuardada->cpd_origen_meta,
                ] : null,
            ];
        }

        $equipoResultado = [];
        foreach ($vendedores as $usuarioId => $fila) {
            $metaComun = $resultado[(int) ($fila['departamento_id'] ?? 0)]['meta_comun'] ?? null;
            $metaIndividual = (float) ($fila['meta'] ?? 0);
            $tasa = (float) ($fila['tasa'] ?? self::TASA_ORDINARIA);
            $equipoResultado[$usuarioId] = [
                'meta' => $metaIndividual > 0 ? round($metaIndividual, 2) : $metaComun,
                'origen' => $metaIndividual > 0 ? 'individual' : ($metaComun !== null ? 'comun' : 'sin_meta'),
                'meta_comun' => $metaComun,
                'requiere_motivo' => $this->requiereMotivoAjuste($metaIndividual, $metaComun, $tasa),
            ];
        }

        return [
            'periodo' => $fecha->format('Y-m'),
            'referencia' => $mesReferencia->format('Y-m'),
            'departamentos' => $resultado,
            'vendedores' => $equipoResultado,
            'lineas_repetidas' => DB::table('tbl_lineas_lna')->whereIn('lna_id', array_unique($lineasRepetidas))->pluck('lna_nombre')->all(),
        ];
    }

    /** Meta sugerida = base histórica ÷ vendedores participantes × (1 + incremento / 100). */
    public function metaSugerida(float $base, int $vendedores, float $incremento): ?float
    {
        if ($base <= 0 || $vendedores <= 0) {
            return null;
        }

        return round(($base / $vendedores) * (1 + ($incremento / 100)), 2);
    }

    /** La meta manual tiene prioridad; con histórico se usa la sugerida. */
    public function resolverMetaComun(string $modo, ?float $metaManual, ?float $sugerida): ?float
    {
        if ($modo === 'manual') {
            return $metaManual !== null && $metaManual > 0 ? round($metaManual, 2) : null;
        }

        return $sugerida;
    }

    /** Una tasa distinta de la ordinaria o una meta distinta de la común exigen motivo. */
    public function requiereMotivoAjuste(float $metaIndividual, ?float $metaComun, float $tasa): bool
    {
        return abs($tasa - self::TASA_ORDINARIA) > 0.00001
            || ($metaIndividual > 0 && $metaComun !== null && $metaComun > 0 && abs($metaIndividual - $metaComun) > 0.009);
    }

    public function modoMeta(array $config): string
    {
        $modo = $config['modo_meta'] ?? null;
        if (in_array($modo, ['historica', 'manual'], true)) {
            return $modo;
        }

        // Compatibilidad: antes una meta común capturada equivalía a meta manual.
        return $this->importePositivo($config['meta_comun'] ?? null) !== null ? 'manual' : 'historica';
    }

    private function importePositivo(mixed $valor): ?float
    {
        return is_numeric($valor) && (float) $valor > 0 ? round((float) $valor, 2) : null;
    }

    public function aprobar(ComisionV2Periodo $periodo, int $usuarioId): ComisionV2Periodo
    {
        return DB::transaction(function () use ($periodo, $usuarioId): ComisionV2Periodo {
            $periodo = ComisionV2Periodo::query()->lockForUpdate()->findOrFail($periodo->cmp_id);
            if ($periodo->cmp_estatus === 'cerrado') {
                throw ValidationException::withMessages(['periodo' => 'El periodo ya está cerrado.']);
            }
            if ($periodo->cmp_estatus === 'aprobado') {
                throw ValidationException::withMessages(['periodo' => 'El periodo ya está aprobado. Guarda cualquier corrección con su motivo.']);
            }
            $periodo->load(['almacenes', 'departamentos.participantes']);
            if ($periodo->almacenes->isEmpty()) {
                throw ValidationException::withMessages(['periodo' => 'Selecciona al menos un almacén.']);
            }
            if ($periodo->departamentos->isEmpty()) {
                throw ValidationException::withMessages(['periodo' => 'Configura al menos un departamento.']);
            }
            foreach ($periodo->departamentos as $departamento) {
                $lineas = DB::table('tbl_comision_v2_periodo_lineas_cml')->where('cml_cpd_id', $departamento->cpd_id)->count();
                if ($lineas === 0 || $departamento->participantes->isEmpty()) {
                    throw ValidationException::withMessages(['periodo' => "{$departamento->cpd_departamento_nombre} necesita líneas y al menos un vendedor."]);
                }
                if ($departamento->participantes->contains(fn ($p) => (float) $p->cpt_meta_individual <= 0)) {
                    throw ValidationException::withMessages(['periodo' => "Captura una meta para todos los vendedores de {$departamento->cpd_departamento_nombre}."]);
                }
            }
            $periodo->update([
                'cmp_estatus' => 'aprobado',
                'cmp_aprobado_at' => now(),
                'cmp_aprobado_by_usr_id' => $usuarioId,
                'cmp_updated_by_usr_id' => $usuarioId,
            ]);
            return $periodo->fresh();
        });
    }

    public function estimacionAdministrativa(ComisionV2Periodo $periodo): Collection
    {
        if ($periodo->cmp_estatus === 'cerrado') {
            return $periodo->resultados()->orderBy('cmr_departamento_nombre')->orderBy('cmr_numero_vendedor')->get()
                ->map(fn ($r) => (object) ([
                    'usuario_id' => (int) $r->cmr_usr_id,
                    'numero' => $r->cmr_numero_vendedor,
                    'nombre' => $r->cmr_nombre_vendedor,
                    'departamento' => $r->cmr_departamento_nombre,
                    'ventas' => (float) $r->cmr_ventas_netas,
                    'meta' => (float) $r->cmr_meta_individual,
                    'cumplimiento' => (float) $r->cmr_cumplimiento,
                    'base_comisionable' => (float) $r->cmr_base_comisionable,
                    'tasa' => (float) $r->cmr_tasa_comision,
                    'comision' => (float) $r->cmr_comision,
                    'definitivo' => true,
                ] + $this->explicarResultado((float) $r->cmr_ventas_netas, (float) $r->cmr_meta_individual, (float) $r->cmr_tasa_comision, (float) $r->cmr_comision)));
        }

        $periodo->load(['almacenes', 'departamentos', 'participantes.departamentoPeriodo']);
        $movimientos = $this->movimientos->obtener($periodo, $periodo->cmp_periodo->copy()->startOfMonth(), $periodo->cmp_periodo->copy()->endOfMonth());
        return $periodo->participantes->map(function ($participante) use ($periodo, $movimientos) {
            $ventas = round((float) $movimientos
                ->where('departamento_periodo_id', $participante->cpt_cpd_id)
                ->where('vendedor_id', $participante->cpt_usr_id)
                ->sum('importe'), 2);
            $calculo = $this->calcularFila($ventas, (float) $participante->cpt_meta_individual, (float) $periodo->cmp_factor_comisionable, (float) $participante->cpt_tasa_comision);
            return (object) ([
                'usuario_id' => (int) $participante->cpt_usr_id,
                'numero' => $participante->cpt_numero_vendedor,
                'nombre' => $participante->cpt_nombre_vendedor,
                'departamento' => $participante->departamentoPeriodo->cpd_departamento_nombre,
                'ventas' => $ventas,
                'meta' => (float) $participante->cpt_meta_individual,
                'cumplimiento' => $calculo['cumplimiento'],
                'base_comisionable' => $calculo['base_comisionable'],
                'tasa' => (float) $participante->cpt_tasa_comision,
                'comision' => $calculo['comision'],
                'definitivo' => false,
            ] + $this->explicarResultado($ventas, (float) $participante->cpt_meta_individual, (float) $participante->cpt_tasa_comision, $calculo['comision']));
        })->sortBy([['departamento', 'asc'], ['numero', 'asc']])->values();
    }

    public function reporte(ComisionV2Periodo $periodo, array $filtros = []): array
    {
        $headers = ['No. vendedor', 'Nombre', 'Ventas netas', 'Cumplimiento %', 'Comisión', 'Departamento', 'Meta', 'Factor comisionable %', 'Tasa %', 'Estado'];
        $estadoCoincide = empty($filtros['estado']) || $filtros['estado'] === $periodo->cmp_estatus;
        $estimaciones = $estadoCoincide && in_array($periodo->cmp_estatus, ['aprobado', 'cerrado'], true)
            ? $this->estimacionAdministrativa($periodo)
            : collect();

        $participantes = $periodo->participantes()->get(['cpt_usr_id', 'cpt_cpd_id'])->keyBy('cpt_usr_id');
        $departamentos = $periodo->departamentos()->pluck('cpd_cmd_id', 'cpd_id');
        $rows = $estimaciones
            ->when($filtros['usuario_id'] ?? null, fn (Collection $filas, $id) => $filas->where('usuario_id', (int) $id))
            ->when($filtros['grupo_id'] ?? null, function (Collection $filas, $id) use ($participantes, $departamentos) {
                return $filas->filter(function ($fila) use ($id, $participantes, $departamentos): bool {
                    $departamentoPeriodoId = $participantes->get($fila->usuario_id)?->cpt_cpd_id;
                    return (int) ($departamentos->get($departamentoPeriodoId) ?? 0) === (int) $id;
                });
            })
            ->map(fn ($fila) => (object) [
                'no_vendedor' => $fila->numero,
                'nombre' => $fila->nombre,
                'ventas_netas' => (float) $fila->ventas,
                'cumplimiento' => (float) $fila->cumplimiento,
                'comision' => (float) $fila->comision,
                'departamento' => $fila->departamento,
                'meta' => (float) $fila->meta,
                'factor_comisionable' => (float) $periodo->cmp_factor_comisionable,
                'tasa' => (float) $fila->tasa,
                'estado' => $fila->definitivo ? 'Definitivo' : 'Estimado',
            ])->values();

        $resultados = $periodo->cmp_estatus === 'cerrado'
            ? $periodo->resultados()->with('detalles')->get()->keyBy('cmr_numero_vendedor')
            : collect();
        $detalles = $rows->mapWithKeys(function ($fila) use ($resultados): array {
            $resultado = $resultados->get($fila->no_vendedor);
            $movimientos = $resultado?->detalles ?? collect();
            return [(string) $fila->no_vendedor => [
                'resumen' => [
                    'venta_bruta' => round((float) $movimientos->sum('crx_venta_bruta'), 2),
                    'descuentos' => round((float) $movimientos->sum('crx_descuentos'), 2),
                    'devoluciones' => round((float) $movimientos->sum('crx_devoluciones'), 2),
                    'venta_neta' => (float) $fila->ventas_netas,
                    'base_comisionable' => round((float) $fila->ventas_netas * ((float) $fila->factor_comisionable / 100), 2),
                    'ventas_sin_atencion' => 0,
                    'vendedores_promedio' => 0,
                    'incremento_meta' => 0,
                    'explicacion_tasa' => (float) $fila->cumplimiento >= 100
                        ? 'Al alcanzar el 100% se aplicó la tasa individual sobre el 33% de las ventas netas.'
                        : 'No generó comisión porque no alcanzó el 100% de la meta.',
                ],
                'almacenes' => $this->agruparDetalleReporte($movimientos, 'crx_almacen_nombre'),
                'lineas' => $this->agruparDetalleReporte($movimientos, 'crx_linea_nombre'),
            ]];
        })->all();

        $detalleExportacion = $resultados->flatMap(fn (ComisionV2Resultado $resultado) => $resultado->detalles->map(fn (ComisionV2ResultadoDetalle $detalle) => (object) [
            'no_vendedor' => $resultado->cmr_numero_vendedor,
            'nombre' => $resultado->cmr_nombre_vendedor,
            'grupo' => $resultado->cmr_departamento_nombre,
            'almacen' => $detalle->crx_almacen_nombre,
            'linea' => $detalle->crx_linea_nombre,
            'venta_bruta' => (float) $detalle->crx_venta_bruta,
            'descuentos' => (float) $detalle->crx_descuentos,
            'devoluciones' => (float) $detalle->crx_devoluciones,
            'venta_neta' => (float) $detalle->crx_venta_neta,
        ]))->values();

        return [
            'encabezados' => $headers,
            'rows' => $rows,
            'kpis' => [
                'Estado' => str($periodo->cmp_estatus)->headline()->toString(),
                'Vendedores' => $rows->count(),
                'Ventas netas' => round((float) $rows->sum('ventas_netas'), 2),
                'Comisiones' => round((float) $rows->sum('comision'), 2),
            ],
            'total_registros' => $rows->count(),
            'estado_periodo' => $periodo->cmp_estatus,
            'detalles' => $detalles,
            'detalle_exportacion' => $detalleExportacion,
        ];
    }

    public function cerrar(ComisionV2Periodo $periodo, int $usuarioId): int
    {
        return DB::transaction(function () use ($periodo, $usuarioId): int {
            $periodo = ComisionV2Periodo::query()->lockForUpdate()->findOrFail($periodo->cmp_id);
            if ($periodo->cmp_estatus !== 'aprobado') {
                throw ValidationException::withMessages(['periodo' => 'El periodo debe estar aprobado antes de cerrarse.']);
            }
            $periodo->load(['almacenes', 'departamentos', 'participantes.departamentoPeriodo']);
            $movimientos = $this->movimientos->obtener($periodo, $periodo->cmp_periodo->copy()->startOfMonth(), $periodo->cmp_periodo->copy()->endOfMonth());
            $periodo->resultados()->delete();
            foreach ($periodo->participantes as $participante) {
                $filas = $movimientos->where('departamento_periodo_id', $participante->cpt_cpd_id)
                    ->where('vendedor_id', $participante->cpt_usr_id);
                $ventas = round((float) $filas->sum('importe'), 2);
                $calculo = $this->calcularFila($ventas, (float) $participante->cpt_meta_individual, (float) $periodo->cmp_factor_comisionable, (float) $participante->cpt_tasa_comision);
                $resultado = ComisionV2Resultado::query()->create([
                    'cmr_cmp_id' => $periodo->cmp_id,
                    'cmr_cpt_id' => $participante->cpt_id,
                    'cmr_usr_id' => $participante->cpt_usr_id,
                    'cmr_numero_vendedor' => $participante->cpt_numero_vendedor,
                    'cmr_nombre_vendedor' => $participante->cpt_nombre_vendedor,
                    'cmr_departamento_nombre' => $participante->departamentoPeriodo->cpd_departamento_nombre,
                    'cmr_ventas_netas' => $ventas,
                    'cmr_meta_individual' => $participante->cpt_meta_individual,
                    'cmr_cumplimiento' => $calculo['cumplimiento'],
                    'cmr_factor_comisionable' => $periodo->cmp_factor_comisionable,
                    'cmr_base_comisionable' => $calculo['base_comisionable'],
                    'cmr_tasa_comision' => $participante->cpt_tasa_comision,
                    'cmr_comision' => $calculo['comision'],
                ]);
                $detalles = $filas->groupBy(fn ($fila) => $fila->almacen_id.'|'.$fila->linea_id)->map(function (Collection $grupo) use ($resultado) {
                    $primera = $grupo->first();
                    return [
                        'crx_cmr_id' => $resultado->cmr_id,
                        'crx_alm_id' => $primera->almacen_id,
                        'crx_almacen_nombre' => $primera->almacen_nombre,
                        'crx_lna_id' => $primera->linea_id,
                        'crx_linea_nombre' => $primera->linea_nombre,
                        'crx_venta_bruta' => round((float) $grupo->sum('venta_bruta'), 2),
                        'crx_descuentos' => round((float) $grupo->sum('descuentos'), 2),
                        'crx_devoluciones' => round((float) $grupo->sum('devoluciones'), 2),
                        'crx_venta_neta' => round((float) $grupo->sum('importe'), 2),
                        'crx_created_at' => now(),
                        'crx_updated_at' => now(),
                    ];
                })->values()->all();
                if ($detalles !== []) {
                    ComisionV2ResultadoDetalle::query()->insert($detalles);
                }
            }
            $periodo->update([
                'cmp_estatus' => 'cerrado',
                'cmp_cerrado_at' => now(),
                'cmp_cerrado_by_usr_id' => $usuarioId,
                'cmp_updated_by_usr_id' => $usuarioId,
            ]);
            return $periodo->resultados()->count();
        });
    }

    public function avancePropio(int $usuarioId, int $sucursalId, ?Carbon $mes = null): array
    {
        $mes ??= now();
        $periodo = ComisionV2Periodo::query()
            ->where('cmp_scl_id', $sucursalId)
            ->whereDate('cmp_periodo', $mes->copy()->startOfMonth()->toDateString())
            ->whereIn('cmp_estatus', ['aprobado', 'cerrado'])
            ->first();
        if (! $periodo) {
            return $this->avanceNoDisponible();
        }
        if ($periodo->cmp_estatus === 'cerrado') {
            $resultado = $periodo->resultados()->where('cmr_usr_id', $usuarioId)->first();
            if (! $resultado) {
                return $this->avanceNoDisponible();
            }
            return $this->formatearAvance((float) $resultado->cmr_cumplimiento, true);
        }
        $participante = $periodo->participantes()->where('cpt_usr_id', $usuarioId)->first();
        if (! $participante) {
            return $this->avanceNoDisponible();
        }
        $estimacion = $this->estimacionAdministrativa($periodo)->firstWhere('usuario_id', $usuarioId);
        return $this->formatearAvance((float) ($estimacion?->cumplimiento ?? 0), false);
    }

    /**
     * Motivo legible del resultado. Usa la misma regla de elegibilidad que calcularFila().
     *
     * @return array{meta_alcanzada: bool, motivo: string, explicacion: string}
     */
    public function explicarResultado(float $ventas, float $meta, float $tasa, float $comision): array
    {
        $alcanzada = $this->metaAlcanzada($ventas, $meta);
        [$motivo, $explicacion] = match (true) {
            $meta <= 0 => ['sin_meta', 'Sin meta asignada: no puede generar comisión.'],
            $ventas <= 0 => ['sin_ventas', 'Sin ventas netas en el periodo.'],
            ! $alcanzada => ['meta_no_alcanzada', 'No alcanzó la meta: le faltan $'.number_format(round($meta - $ventas, 2), 2).'.'],
            $tasa <= 0 => ['tasa_cero', 'Alcanzó la meta, pero su tasa es 0%.'],
            $comision <= 0 => ['comision_cero', 'Alcanzó la meta; el importe redondeado es $0.00.'],
            default => ['comisiona', 'Alcanzó la meta: tasa aplicada al 33% de sus ventas netas.'],
        };

        return ['meta_alcanzada' => $alcanzada, 'motivo' => $motivo, 'explicacion' => $explicacion];
    }

    public function calcularFila(float $ventas, float $meta, float $factor, float $tasa): array
    {
        $metaAlcanzada = $this->metaAlcanzada($ventas, $meta);
        $cumplimiento = $meta > 0 ? round(max(0, $ventas) / $meta * 100, 2) : 0.0;
        // El redondeo visual no debe anunciar una meta que todavía no se alcanza.
        if (! $metaAlcanzada) {
            $cumplimiento = min(99.99, $cumplimiento);
        }
        $base = round(max(0, $ventas) * ($factor / 100), 2);
        $comision = $metaAlcanzada ? round($base * ($tasa / 100), 2) : 0.0;
        return ['cumplimiento' => $cumplimiento, 'base_comisionable' => $base, 'comision' => $comision];
    }

    /** Solo hay derecho a comisión con meta positiva y ventas netas mayores o iguales a la meta. */
    private function metaAlcanzada(float $ventas, float $meta): bool
    {
        return $meta > 0 && $ventas >= $meta;
    }

    private function avanceNoDisponible(): array
    {
        return ['estado' => 'sin_meta', 'porcentaje' => null, 'mensaje' => 'Tu meta aún no está disponible.'];
    }

    private function formatearAvance(float $porcentajeReal, bool $cerrado): array
    {
        $visible = round(min(100, max(0, $porcentajeReal)), 2);
        $mensaje = match (true) {
            $visible >= 100 => '¡Meta alcanzada! Gran trabajo en equipo.',
            $visible >= 80 => 'Estás muy cerca. Sigue avanzando.',
            $visible >= 50 => 'Buen avance. Cada venta te acerca a la meta.',
            $visible > 0 => 'Tu avance ya comenzó. Sigue adelante.',
            default => 'Tu avance comenzará con tus primeras ventas del mes.',
        };
        return ['estado' => $cerrado ? 'cerrado' : ($visible >= 100 ? 'meta_alcanzada' : 'en_progreso'), 'porcentaje' => $visible, 'mensaje' => $mensaje];
    }

    private function agruparDetalleReporte(Collection $detalles, string $campo): array
    {
        return $detalles->groupBy($campo)->map(fn (Collection $filas, string $nombre) => [
            'nombre' => $nombre,
            'venta_bruta' => round((float) $filas->sum('crx_venta_bruta'), 2),
            'descuentos' => round((float) $filas->sum('crx_descuentos'), 2),
            'devoluciones' => round((float) $filas->sum('crx_devoluciones'), 2),
            'venta_neta' => round((float) $filas->sum('crx_venta_neta'), 2),
        ])->values()->all();
    }
}
