<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_permisos_prm', function (Blueprint $table) {
            $table->bigIncrements('prm_id');
            $table->string('prm_clave', 120)->unique();
            $table->string('prm_descripcion', 220);
            $table->string('prm_modulo', 60)->index();
            $table->string('prm_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('prm_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('prm_updated_by_usr_id')->nullable()->index();
            $table->boolean('prm_deleted')->default(false)->index();
            $table->timestamp('prm_deleted_at')->nullable()->index();
            $table->timestamp('prm_created_at')->nullable();
            $table->timestamp('prm_updated_at')->nullable();

            $table->index(['prm_deleted', 'prm_estatus']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_permisos_prm');
    }
};
