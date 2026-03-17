<?php

namespace Database\Seeders;

use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\ChecklistSeccion;
use Illuminate\Database\Seeder;

class ChecklistBaseSeeder extends Seeder
{
    public function run(): void
    {
        $plantilla = Checklist::query()
            ->withDeleted()
            ->where('chk_nombre', 'Plantilla entregables base')
            ->first();

        if ($plantilla) {
            $plantilla->update([
                'chk_referencia' => 'Base proyecto retail',
                'chk_fecha' => now()->toDateString(),
                'chk_estatus_general' => 'pendiente',
                'chk_es_plantilla' => true,
                'chk_observaciones' => 'Plantilla editable para revisiones con cliente.',
                'chk_deleted' => false,
                'chk_deleted_at' => null,
            ]);
        } else {
            $plantilla = Checklist::query()->create([
                'chk_nombre' => 'Plantilla entregables base',
                'chk_referencia' => 'Base proyecto retail',
                'chk_fecha' => now()->toDateString(),
                'chk_estatus_general' => 'pendiente',
                'chk_es_plantilla' => true,
                'chk_observaciones' => 'Plantilla editable para revisiones con cliente.',
            ]);
        }

        $estructura = [
            [
                'titulo' => 'Usuarios y seguridad',
                'descripcion' => 'Acceso, roles y permisos base por perfil.',
                'items' => [
                    ['titulo' => 'Login funcional', 'descripcion' => 'Inicio de sesión válido para usuarios activos.', 'referencia' => 'Navegación > Seguridad > Login'],
                    ['titulo' => 'Roles estándar creados', 'descripcion' => 'Administrador, Supervisor, Cajero y Vendedor piso disponibles.', 'referencia' => 'Seguridad > Roles'],
                    ['titulo' => 'Permisos base aplicados', 'descripcion' => 'Menús y acciones visibles/ejecutables según permiso.', 'referencia' => 'Seguridad > Permisos + Menú'],
                ],
            ],
            [
                'titulo' => 'Multi-sucursal base',
                'descripcion' => 'Configuración de sucursales, almacenes y asignación de usuarios.',
                'items' => [
                    ['titulo' => 'Alta y administración de sucursales', 'descripcion' => 'CRUD de sucursales y tipos de almacén.', 'referencia' => 'Operación > Sucursales y almacenes'],
                    ['titulo' => 'Asignación de usuarios a sucursal', 'descripcion' => 'Usuario con una o más sucursales y predeterminada.', 'referencia' => 'Seguridad > Usuarios'],
                    ['titulo' => 'Base para inventario/ventas por sucursal', 'descripcion' => 'Estructura operativa lista para integración de módulos transaccionales.', 'referencia' => 'Operación > Sucursales y almacenes'],
                ],
            ],
            [
                'titulo' => 'Catálogos base',
                'descripcion' => 'Catálogos editables para captura estandarizada.',
                'items' => [
                    ['titulo' => 'Marcas, líneas y categorías', 'descripcion' => 'Catálogos comerciales base activos y editables.', 'referencia' => 'Operación > Catálogo comercial > Catálogos base'],
                    ['titulo' => 'Atributos y valores (tallas/colores/acabados equivalentes)', 'descripcion' => 'Catálogo parametrizable por atributo y valores.', 'referencia' => 'Operación > Catálogo comercial > Atributos'],
                    ['titulo' => 'Motivos/Conceptos de operación', 'descripcion' => 'Catálogos para cancelación/devolución/ajuste según alcance.', 'referencia' => 'Operación > Catálogos específicos'],
                    ['titulo' => 'Proveedores (alta básica)', 'descripcion' => 'Alta de proveedor con datos básicos y contactos.', 'referencia' => 'Operación > Catálogo comercial > Proveedores'],
                    ['titulo' => 'Validaciones contra catálogo', 'descripcion' => 'Evita valores libres donde aplique y valida referencias activas.', 'referencia' => 'Formularios y reglas de validación'],
                ],
            ],
            [
                'titulo' => 'Productos',
                'descripcion' => 'Alta y edición de productos con campos operativos.',
                'items' => [
                    ['titulo' => 'Alta/edición de producto base', 'descripcion' => 'Código/SKU base, descripción, precio y clasificación por catálogo.', 'referencia' => 'Operación > Catálogo comercial > Productos'],
                    ['titulo' => 'Asignación a catálogos', 'descripcion' => 'Marca, línea, categoría, unidad y atributos relacionados.', 'referencia' => 'Operación > Catálogo comercial > Productos'],
                    ['titulo' => 'Búsqueda rápida por texto/código', 'descripcion' => 'Consulta de productos por código o texto.', 'referencia' => 'Operación > Catálogo comercial > Productos'],
                ],
            ],
            [
                'titulo' => 'Escaneo',
                'descripcion' => 'Consulta por escaneo y vista de existencia por sucursal.',
                'items' => [
                    ['titulo' => 'Consulta por escaneo', 'descripcion' => 'Lectura por lector/cámara según dispositivo.', 'referencia' => 'Módulo de consulta por escaneo'],
                    ['titulo' => 'Vista de producto y existencia por sucursal', 'descripcion' => 'Detalle esencial del producto con stock por sucursal.', 'referencia' => 'Consulta de producto'],
                ],
            ],
        ];

        foreach ($estructura as $indiceSeccion => $seccionData) {
            $seccion = ChecklistSeccion::query()
                ->withDeleted()
                ->where('chs_chk_id', $plantilla->chk_id)
                ->where('chs_titulo', $seccionData['titulo'])
                ->first();

            if ($seccion) {
                $seccion->update([
                    'chs_descripcion' => $seccionData['descripcion'],
                    'chs_observacion' => null,
                    'chs_orden' => $indiceSeccion + 1,
                    'chs_estatus' => 'activo',
                    'chs_deleted' => false,
                    'chs_deleted_at' => null,
                ]);
            } else {
                $seccion = ChecklistSeccion::query()->create([
                    'chs_chk_id' => $plantilla->chk_id,
                    'chs_titulo' => $seccionData['titulo'],
                    'chs_descripcion' => $seccionData['descripcion'],
                    'chs_observacion' => null,
                    'chs_orden' => $indiceSeccion + 1,
                    'chs_estatus' => 'activo',
                ]);
            }

            foreach ($seccionData['items'] as $indiceItem => $itemData) {
                $item = ChecklistItem::query()
                    ->withDeleted()
                    ->where('chi_chs_id', $seccion->chs_id)
                    ->where('chi_titulo', $itemData['titulo'])
                    ->first();

                if ($item) {
                    $item->update([
                        'chi_descripcion' => $itemData['descripcion'],
                        'chi_referencia_funcional' => $itemData['referencia'],
                        'chi_estatus' => 'pendiente',
                        'chi_observacion' => null,
                        'chi_orden' => $indiceItem + 1,
                        'chi_deleted' => false,
                        'chi_deleted_at' => null,
                    ]);
                } else {
                    ChecklistItem::query()->create([
                        'chi_chs_id' => $seccion->chs_id,
                        'chi_titulo' => $itemData['titulo'],
                        'chi_descripcion' => $itemData['descripcion'],
                        'chi_referencia_funcional' => $itemData['referencia'],
                        'chi_estatus' => 'pendiente',
                        'chi_observacion' => null,
                        'chi_orden' => $indiceItem + 1,
                    ]);
                }
            }
        }
    }
}
