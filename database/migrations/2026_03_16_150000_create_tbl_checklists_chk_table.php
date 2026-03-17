<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_checklists_chk', function (Blueprint $table) {
            $table->bigIncrements('chk_id');
            $table->string('chk_nombre', 180);
            $table->string('chk_referencia', 180)->nullable()->index();
            $table->date('chk_fecha')->index();
            $table->string('chk_estatus_general', 20)->default('pendiente')->index();
            $table->boolean('chk_es_plantilla')->default(false)->index();
            $table->text('chk_observaciones')->nullable();
            $table->unsignedBigInteger('chk_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('chk_updated_by_usr_id')->nullable()->index();
            $table->boolean('chk_deleted')->default(false)->index();
            $table->timestamp('chk_deleted_at')->nullable()->index();
            $table->timestamp('chk_created_at')->nullable();
            $table->timestamp('chk_updated_at')->nullable();

            $table->index(['chk_deleted', 'chk_estatus_general']);
            $table->index(['chk_es_plantilla', 'chk_deleted']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_checklists_chk');
    }
};
