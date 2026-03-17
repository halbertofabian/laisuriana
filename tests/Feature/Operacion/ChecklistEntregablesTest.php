<?php

namespace Tests\Feature\Operacion;

use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChecklistEntregablesTest extends TestCase
{
    use RefreshDatabase;

    public function test_modulo_requiere_permiso_para_acceso(): void
    {
        $this->seed(DatabaseSeeder::class);

        $usuarioSinPermisos = Usuario::query()->create([
            'usr_nombre' => 'Usuario sin permisos',
            'usr_usuario' => 'usuario_sin_permisos',
            'usr_email' => 'sin.permisos@lasuriana.local',
            'usr_password' => Hash::make('Password123!'),
            'usr_estatus' => 'activo',
        ]);

        $this->actingAs($usuarioSinPermisos);

        $this->get(route('operacion.checklist_entregables.index'))
            ->assertForbidden();
    }

    public function test_menu_checklist_se_muestra_solo_a_usuarios_con_permiso(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = Usuario::query()->where('usr_usuario', 'admin')->firstOrFail();
        $this->actingAs($admin);
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Checklist entregables');

        $usuarioSinPermisos = Usuario::query()->create([
            'usr_nombre' => 'Usuario sin menú checklist',
            'usr_usuario' => 'usuario_sin_menu_chk',
            'usr_email' => 'sin.menu.checklist@lasuriana.local',
            'usr_password' => Hash::make('Password123!'),
            'usr_estatus' => 'activo',
        ]);

        $this->actingAs($usuarioSinPermisos);
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Checklist entregables');
    }

    public function test_admin_puede_crear_checklist_y_actualizar_revision_de_item(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = Usuario::query()->where('usr_usuario', 'admin')->firstOrFail();
        $this->actingAs($admin);

        $storeChecklist = $this->postJson(route('operacion.checklist_entregables.store'), [
            'chk_nombre' => 'Checklist QA Entregables',
            'chk_referencia' => 'Cliente QA',
            'chk_fecha' => now()->toDateString(),
            'chk_observaciones' => 'Prueba de guardado básico.',
            'usar_plantilla_base' => false,
        ]);

        $storeChecklist->assertOk();
        $checklistId = (int) data_get($storeChecklist->json(), 'data.chk_id');

        $storeSeccion = $this->postJson(route('operacion.checklist_entregables.secciones.store', ['checklist' => $checklistId]), [
            'chs_titulo' => 'Usuarios y seguridad',
            'chs_descripcion' => 'Validaciones de seguridad.',
            'chs_orden' => 1,
        ]);

        $storeSeccion->assertOk();
        $seccionId = (int) data_get($storeSeccion->json(), 'data.chs_id');

        $storeItem = $this->postJson(route('operacion.checklist_entregables.items.store', ['seccion' => $seccionId]), [
            'chi_titulo' => 'Login funcional',
            'chi_descripcion' => 'Debe permitir acceso a usuario activo.',
            'chi_referencia_funcional' => 'Seguridad > Login',
            'chi_estatus' => 'pendiente',
            'chi_orden' => 1,
        ]);

        $storeItem->assertOk();
        $itemId = (int) data_get($storeItem->json(), 'data.chi_id');

        $this->patchJson(route('operacion.checklist_entregables.items.revision', ['item' => $itemId]), [
            'chi_estatus' => 'observado',
            'chi_observacion' => 'Se detectó validación pendiente.',
        ])->assertOk();

        $this->assertDatabaseHas('tbl_checklist_items_chi', [
            'chi_id' => $itemId,
            'chi_estatus' => 'observado',
            'chi_observacion' => 'Se detectó validación pendiente.',
        ]);

        $this->assertDatabaseHas('tbl_checklists_chk', [
            'chk_id' => $checklistId,
            'chk_estatus_general' => 'observado',
        ]);

        $detalle = $this->getJson(route('operacion.checklist_entregables.detalle', ['checklist' => $checklistId]));

        $detalle->assertOk();
        $this->assertSame('observado', data_get($detalle->json(), 'data.chk_estatus_general'));
        $this->assertSame(1, data_get($detalle->json(), 'data.resumen.observado'));

        $registro = Checklist::query()->findOrFail($checklistId);
        $this->assertSame('Checklist QA Entregables', $registro->chk_nombre);
        $this->assertSame(1, ChecklistItem::query()->where('chi_chs_id', $seccionId)->count());
    }
}
