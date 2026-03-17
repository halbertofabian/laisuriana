<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_checklist_secciones_chs', function (Blueprint $table) {
            $table->bigIncrements('chs_id');
            $table->unsignedBigInteger('chs_chk_id');
            $table->string('chs_titulo', 160);
            $table->text('chs_descripcion')->nullable();
            $table->text('chs_observacion')->nullable();
            $table->unsignedInteger('chs_orden')->default(1);
            $table->string('chs_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('chs_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('chs_updated_by_usr_id')->nullable()->index();
            $table->boolean('chs_deleted')->default(false)->index();
            $table->timestamp('chs_deleted_at')->nullable()->index();
            $table->timestamp('chs_created_at')->nullable();
            $table->timestamp('chs_updated_at')->nullable();

            $table->foreign('chs_chk_id')->references('chk_id')->on('tbl_checklists_chk');
            $table->unique(['chs_chk_id', 'chs_titulo', 'chs_deleted'], 'uk_chk_seccion_titulo_activo');
            $table->index(['chs_chk_id', 'chs_deleted', 'chs_orden'], 'idx_chs_chk_del_ord');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_checklist_secciones_chs');
    }
};
