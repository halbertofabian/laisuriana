<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_roles_rol', function (Blueprint $table) {
            $table->bigIncrements('rol_id');
            $table->string('rol_nombre', 100)->unique();
            $table->string('rol_descripcion', 220)->nullable();
            $table->string('rol_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('rol_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('rol_updated_by_usr_id')->nullable()->index();
            $table->boolean('rol_deleted')->default(false)->index();
            $table->timestamp('rol_deleted_at')->nullable()->index();
            $table->timestamp('rol_created_at')->nullable();
            $table->timestamp('rol_updated_at')->nullable();

            $table->index(['rol_deleted', 'rol_estatus']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_roles_rol');
    }
};
