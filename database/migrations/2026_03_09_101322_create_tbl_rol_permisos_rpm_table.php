<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_rol_permisos_rpm', function (Blueprint $table) {
            $table->bigIncrements('rpm_id');
            $table->unsignedBigInteger('rpm_rol_id');
            $table->unsignedBigInteger('rpm_prm_id');
            $table->string('rpm_estatus', 20)->default('activo')->index();
            $table->boolean('rpm_deleted')->default(false)->index();
            $table->timestamp('rpm_deleted_at')->nullable()->index();
            $table->timestamp('rpm_created_at')->nullable();
            $table->timestamp('rpm_updated_at')->nullable();

            $table->foreign('rpm_rol_id')->references('rol_id')->on('tbl_roles_rol');
            $table->foreign('rpm_prm_id')->references('prm_id')->on('tbl_permisos_prm');
            $table->unique(['rpm_rol_id', 'rpm_prm_id', 'rpm_deleted'], 'uk_rol_permiso_activo');
            $table->index(['rpm_rol_id', 'rpm_deleted']);
            $table->index(['rpm_prm_id', 'rpm_deleted']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_rol_permisos_rpm');
    }
};
