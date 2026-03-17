<?php

namespace App\Services\Operacion;

use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\ChecklistSeccion;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ChecklistEntregableService
{
    public function __construct(private readonly AuditoriaService $auditoriaService)
    {
    }

    public function listar(array $filtros = []): Collection
    {
        return Checklist::query()
            ->withCount([
                'items as items_total',
                'items as items_pendiente' => fn ($query) => $query->where('chi_estatus', 'pendiente'),
                'items as items_aprobado' => fn ($query) => $query->where('chi_estatus', 'aprobado'),
                'items as items_observado' => fn ($query) => $query->where('chi_estatus', 'observado'),
                'items as items_no_aplica' => fn ($query) => $query->where('chi_estatus', 'no_aplica'),
            ])
            ->when(!empty($filtros['buscar']), function ($query) use ($filtros): void {
                $buscar = trim((string) $filtros['buscar']);
                $query->where(function ($subQuery) use ($buscar): void {
                    $subQuery->where('chk_nombre', 'like', "%{$buscar}%")
                        ->orWhere('chk_referencia', 'like', "%{$buscar}%");
                });
            })
            ->orderByDesc('chk_es_plantilla')
            ->orderByDesc('chk_fecha')
            ->orderByDesc('chk_id')
            ->get();
    }

    public function obtenerDetalle(int $checklistId): Checklist
    {
        return Checklist::query()
            ->with([
                'secciones' => fn ($query) => $query
                    ->orderBy('chs_orden')
                    ->orderBy('chs_id')
                    ->with([
                        'items' => fn ($itemQuery) => $itemQuery
                            ->orderBy('chi_orden')
                            ->orderBy('chi_id'),
                    ]),
            ])
            ->findOrFail($checklistId);
    }

    public function crear(Request $request, array $datos): Checklist
    {
        return DB::transaction(function () use ($request, $datos): Checklist {
            $checklist = Checklist::query()->create([
                'chk_nombre' => $datos['chk_nombre'],
                'chk_referencia' => Arr::get($datos, 'chk_referencia'),
                'chk_fecha' => $datos['chk_fecha'],
                'chk_estatus_general' => Arr::get($datos, 'chk_estatus_general', 'pendiente'),
                'chk_es_plantilla' => false,
                'chk_observaciones' => Arr::get($datos, 'chk_observaciones'),
                'chk_created_by_usr_id' => optional($request->user())->usr_id,
                'chk_updated_by_usr_id' => optional($request->user())->usr_id,
            ]);

            if (filter_var(Arr::get($datos, 'usar_plantilla_base', true), FILTER_VALIDATE_BOOLEAN)) {
                $this->clonarDesdePlantillaBase($request, $checklist);
            }

            $this->recalcularEstatusGeneral($checklist->chk_id, optional($request->user())->usr_id);

            $this->auditoriaService->registrarAccion(
                $request,
                'checklist_entregables.crear',
                'tbl_checklists_chk',
                (string) $checklist->chk_id,
                [
                    'chk_nombre' => $checklist->chk_nombre,
                    'chk_estatus_general' => $checklist->chk_estatus_general,
                ]
            );

            return $checklist;
        });
    }

    public function actualizar(Request $request, int $checklistId, array $datos): Checklist
    {
        $checklist = Checklist::query()->findOrFail($checklistId);

        $checklist->update([
            'chk_nombre' => $datos['chk_nombre'],
            'chk_referencia' => Arr::get($datos, 'chk_referencia'),
            'chk_fecha' => $datos['chk_fecha'],
            'chk_estatus_general' => $datos['chk_estatus_general'],
            'chk_observaciones' => Arr::get($datos, 'chk_observaciones'),
            'chk_updated_by_usr_id' => optional($request->user())->usr_id,
        ]);

        $this->auditoriaService->registrarAccion(
            $request,
            'checklist_entregables.editar',
            'tbl_checklists_chk',
            (string) $checklist->chk_id,
            [
                'chk_nombre' => $checklist->chk_nombre,
                'chk_estatus_general' => $checklist->chk_estatus_general,
            ]
        );

        return $checklist;
    }

    public function crearSeccion(Request $request, int $checklistId, array $datos): ChecklistSeccion
    {
        $checklist = Checklist::query()->findOrFail($checklistId);

        $orden = Arr::get($datos, 'chs_orden');
        if (!$orden) {
            $orden = ((int) ChecklistSeccion::query()->where('chs_chk_id', $checklist->chk_id)->max('chs_orden')) + 1;
        }

        $seccion = ChecklistSeccion::query()->create([
            'chs_chk_id' => $checklist->chk_id,
            'chs_titulo' => $datos['chs_titulo'],
            'chs_descripcion' => Arr::get($datos, 'chs_descripcion'),
            'chs_observacion' => Arr::get($datos, 'chs_observacion'),
            'chs_orden' => $orden,
            'chs_estatus' => Arr::get($datos, 'chs_estatus', 'activo'),
            'chs_created_by_usr_id' => optional($request->user())->usr_id,
            'chs_updated_by_usr_id' => optional($request->user())->usr_id,
        ]);

        $this->auditoriaService->registrarAccion(
            $request,
            'checklist_entregables.seccion.crear',
            'tbl_checklist_secciones_chs',
            (string) $seccion->chs_id,
            [
                'chs_chk_id' => $checklist->chk_id,
                'chs_titulo' => $seccion->chs_titulo,
            ]
        );

        return $seccion;
    }

    public function crearItem(Request $request, int $seccionId, array $datos): ChecklistItem
    {
        $seccion = ChecklistSeccion::query()->findOrFail($seccionId);

        $orden = Arr::get($datos, 'chi_orden');
        if (!$orden) {
            $orden = ((int) ChecklistItem::query()->where('chi_chs_id', $seccion->chs_id)->max('chi_orden')) + 1;
        }

        $item = ChecklistItem::query()->create([
            'chi_chs_id' => $seccion->chs_id,
            'chi_titulo' => $datos['chi_titulo'],
            'chi_descripcion' => Arr::get($datos, 'chi_descripcion'),
            'chi_referencia_funcional' => Arr::get($datos, 'chi_referencia_funcional'),
            'chi_estatus' => Arr::get($datos, 'chi_estatus', 'pendiente'),
            'chi_observacion' => Arr::get($datos, 'chi_observacion'),
            'chi_orden' => $orden,
            'chi_created_by_usr_id' => optional($request->user())->usr_id,
            'chi_updated_by_usr_id' => optional($request->user())->usr_id,
        ]);

        $this->recalcularEstatusGeneral($seccion->chs_chk_id, optional($request->user())->usr_id);

        $this->auditoriaService->registrarAccion(
            $request,
            'checklist_entregables.item.crear',
            'tbl_checklist_items_chi',
            (string) $item->chi_id,
            [
                'chi_chs_id' => $seccion->chs_id,
                'chi_titulo' => $item->chi_titulo,
            ]
        );

        return $item;
    }

    public function actualizarRevisionItem(Request $request, int $itemId, array $datos): ChecklistItem
    {
        $item = ChecklistItem::query()->with('seccion:chs_id,chs_chk_id')->findOrFail($itemId);

        $item->update([
            'chi_estatus' => $datos['chi_estatus'],
            'chi_observacion' => Arr::get($datos, 'chi_observacion'),
            'chi_updated_by_usr_id' => optional($request->user())->usr_id,
        ]);

        $this->recalcularEstatusGeneral((int) $item->seccion->chs_chk_id, optional($request->user())->usr_id);

        $this->auditoriaService->registrarAccion(
            $request,
            'checklist_entregables.item.revision',
            'tbl_checklist_items_chi',
            (string) $item->chi_id,
            [
                'chi_estatus' => $item->chi_estatus,
            ]
        );

        return $item;
    }

    private function clonarDesdePlantillaBase(Request $request, Checklist $checklistDestino): void
    {
        $template = Checklist::query()
            ->where('chk_es_plantilla', true)
            ->with([
                'secciones' => fn ($query) => $query
                    ->orderBy('chs_orden')
                    ->with(['items' => fn ($itemQuery) => $itemQuery->orderBy('chi_orden')]),
            ])
            ->first();

        if (!$template) {
            return;
        }

        foreach ($template->secciones as $seccionTemplate) {
            $seccionNueva = ChecklistSeccion::query()->create([
                'chs_chk_id' => $checklistDestino->chk_id,
                'chs_titulo' => $seccionTemplate->chs_titulo,
                'chs_descripcion' => $seccionTemplate->chs_descripcion,
                'chs_observacion' => null,
                'chs_orden' => $seccionTemplate->chs_orden,
                'chs_estatus' => 'activo',
                'chs_created_by_usr_id' => optional($request->user())->usr_id,
                'chs_updated_by_usr_id' => optional($request->user())->usr_id,
            ]);

            foreach ($seccionTemplate->items as $itemTemplate) {
                ChecklistItem::query()->create([
                    'chi_chs_id' => $seccionNueva->chs_id,
                    'chi_titulo' => $itemTemplate->chi_titulo,
                    'chi_descripcion' => $itemTemplate->chi_descripcion,
                    'chi_referencia_funcional' => $itemTemplate->chi_referencia_funcional,
                    'chi_estatus' => 'pendiente',
                    'chi_observacion' => null,
                    'chi_orden' => $itemTemplate->chi_orden,
                    'chi_created_by_usr_id' => optional($request->user())->usr_id,
                    'chi_updated_by_usr_id' => optional($request->user())->usr_id,
                ]);
            }
        }
    }

    private function recalcularEstatusGeneral(int $checklistId, ?int $usuarioId): void
    {
        $checklist = Checklist::query()->findOrFail($checklistId);

        $totales = ChecklistItem::query()
            ->selectRaw('chi_estatus, COUNT(*) as total')
            ->join('tbl_checklist_secciones_chs as chs', 'chs.chs_id', '=', 'tbl_checklist_items_chi.chi_chs_id')
            ->where('chs.chs_chk_id', $checklist->chk_id)
            ->groupBy('chi_estatus')
            ->pluck('total', 'chi_estatus');

        $totalItems = (int) $totales->sum();
        $observados = (int) ($totales['observado'] ?? 0);
        $aprobados = (int) ($totales['aprobado'] ?? 0);
        $noAplica = (int) ($totales['no_aplica'] ?? 0);

        $estatus = 'pendiente';

        if ($totalItems === 0) {
            $estatus = 'pendiente';
        } elseif ($observados > 0) {
            $estatus = 'observado';
        } elseif (($aprobados + $noAplica) === $totalItems) {
            $estatus = 'aprobado';
        } elseif (($aprobados + $noAplica) > 0) {
            $estatus = 'en_revision';
        }

        $checklist->update([
            'chk_estatus_general' => $estatus,
            'chk_updated_by_usr_id' => $usuarioId,
        ]);
    }
}
