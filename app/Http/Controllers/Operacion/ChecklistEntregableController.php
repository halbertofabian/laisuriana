<?php

namespace App\Http\Controllers\Operacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operacion\Checklist\StoreChecklistItemRequest;
use App\Http\Requests\Operacion\Checklist\StoreChecklistRequest;
use App\Http\Requests\Operacion\Checklist\StoreChecklistSeccionRequest;
use App\Http\Requests\Operacion\Checklist\UpdateChecklistItemRevisionRequest;
use App\Http\Requests\Operacion\Checklist\UpdateChecklistRequest;
use App\Services\Operacion\ChecklistEntregableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChecklistEntregableController extends Controller
{
    public function __construct(private readonly ChecklistEntregableService $checklistService)
    {
    }

    public function index()
    {
        return view('operacion.checklist_entregables.index', [
            'permisosUI' => [
                'crear' => auth()->user()?->tienePermiso('checklist_entregables.crear') ?? false,
                'editar' => auth()->user()?->tienePermiso('checklist_entregables.editar') ?? false,
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $registros = $this->checklistService->listar([
            'buscar' => $request->query('buscar'),
        ]);

        $data = $registros->map(fn ($item) => [
            'chk_id' => $item->chk_id,
            'chk_nombre' => $item->chk_nombre,
            'chk_referencia' => $item->chk_referencia,
            'chk_fecha' => optional($item->chk_fecha)->format('Y-m-d'),
            'chk_estatus_general' => $item->chk_estatus_general,
            'chk_es_plantilla' => (bool) $item->chk_es_plantilla,
            'items_total' => (int) ($item->items_total ?? 0),
            'items_pendiente' => (int) ($item->items_pendiente ?? 0),
            'items_aprobado' => (int) ($item->items_aprobado ?? 0),
            'items_observado' => (int) ($item->items_observado ?? 0),
            'items_no_aplica' => (int) ($item->items_no_aplica ?? 0),
        ])->values();

        return response()->json(['data' => $data]);
    }

    public function detalle(int $checklist): JsonResponse
    {
        $registro = $this->checklistService->obtenerDetalle($checklist);

        $secciones = $registro->secciones->map(function ($seccion): array {
            return [
                'chs_id' => $seccion->chs_id,
                'chs_titulo' => $seccion->chs_titulo,
                'chs_descripcion' => $seccion->chs_descripcion,
                'chs_observacion' => $seccion->chs_observacion,
                'chs_orden' => (int) $seccion->chs_orden,
                'items' => $seccion->items->map(fn ($item) => [
                    'chi_id' => $item->chi_id,
                    'chi_titulo' => $item->chi_titulo,
                    'chi_descripcion' => $item->chi_descripcion,
                    'chi_referencia_funcional' => $item->chi_referencia_funcional,
                    'chi_estatus' => $item->chi_estatus,
                    'chi_observacion' => $item->chi_observacion,
                    'chi_orden' => (int) $item->chi_orden,
                ])->values(),
            ];
        })->values();

        $items = $secciones->flatMap(fn ($seccion) => $seccion['items']);

        $resumen = [
            'total' => $items->count(),
            'pendiente' => $items->where('chi_estatus', 'pendiente')->count(),
            'aprobado' => $items->where('chi_estatus', 'aprobado')->count(),
            'observado' => $items->where('chi_estatus', 'observado')->count(),
            'no_aplica' => $items->where('chi_estatus', 'no_aplica')->count(),
        ];

        return response()->json([
            'data' => [
                'chk_id' => $registro->chk_id,
                'chk_nombre' => $registro->chk_nombre,
                'chk_referencia' => $registro->chk_referencia,
                'chk_fecha' => optional($registro->chk_fecha)->format('Y-m-d'),
                'chk_estatus_general' => $registro->chk_estatus_general,
                'chk_es_plantilla' => (bool) $registro->chk_es_plantilla,
                'chk_observaciones' => $registro->chk_observaciones,
                'resumen' => $resumen,
                'secciones' => $secciones,
            ],
        ]);
    }

    public function store(StoreChecklistRequest $request): JsonResponse
    {
        $item = $this->checklistService->crear($request, $request->validated());

        return response()->json([
            'message' => 'Checklist creado correctamente.',
            'data' => ['chk_id' => $item->chk_id],
        ]);
    }

    public function update(UpdateChecklistRequest $request, int $checklist): JsonResponse
    {
        $this->checklistService->actualizar($request, $checklist, $request->validated());

        return response()->json([
            'message' => 'Checklist actualizado correctamente.',
        ]);
    }

    public function storeSeccion(StoreChecklistSeccionRequest $request, int $checklist): JsonResponse
    {
        $seccion = $this->checklistService->crearSeccion($request, $checklist, $request->validated());

        return response()->json([
            'message' => 'Sección creada correctamente.',
            'data' => ['chs_id' => $seccion->chs_id],
        ]);
    }

    public function storeItem(StoreChecklistItemRequest $request, int $seccion): JsonResponse
    {
        $item = $this->checklistService->crearItem($request, $seccion, $request->validated());

        return response()->json([
            'message' => 'Ítem creado correctamente.',
            'data' => ['chi_id' => $item->chi_id],
        ]);
    }

    public function actualizarRevisionItem(UpdateChecklistItemRevisionRequest $request, int $item): JsonResponse
    {
        $this->checklistService->actualizarRevisionItem($request, $item, $request->validated());

        return response()->json([
            'message' => 'Revisión del ítem actualizada correctamente.',
        ]);
    }
}
