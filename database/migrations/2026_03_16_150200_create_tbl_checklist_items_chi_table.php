<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_checklist_items_chi', function (Blueprint $table) {
            $table->bigIncrements('chi_id');
            $table->unsignedBigInteger('chi_chs_id');
            $table->string('chi_titulo', 180);
            $table->text('chi_descripcion')->nullable();
            $table->string('chi_referencia_funcional', 220)->nullable();
            $table->string('chi_estatus', 20)->default('pendiente')->index();
            $table->text('chi_observacion')->nullable();
            $table->unsignedInteger('chi_orden')->default(1);
            $table->unsignedBigInteger('chi_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('chi_updated_by_usr_id')->nullable()->index();
            $table->boolean('chi_deleted')->default(false)->index();
            $table->timestamp('chi_deleted_at')->nullable()->index();
            $table->timestamp('chi_created_at')->nullable();
            $table->timestamp('chi_updated_at')->nullable();

            $table->foreign('chi_chs_id')->references('chs_id')->on('tbl_checklist_secciones_chs');
            $table->index(['chi_chs_id', 'chi_deleted', 'chi_orden'], 'idx_chi_chs_del_ord');
            $table->index(['chi_chs_id', 'chi_deleted', 'chi_estatus'], 'idx_chi_chs_del_est');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_checklist_items_chi');
    }
};
