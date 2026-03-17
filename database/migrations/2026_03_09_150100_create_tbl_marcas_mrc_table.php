<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_marcas_mrc', function (Blueprint $table) {
            $table->bigIncrements('mrc_id');
            $table->string('mrc_nombre', 120);
            $table->string('mrc_clave', 40);
            $table->string('mrc_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('mrc_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('mrc_updated_by_usr_id')->nullable()->index();
            $table->boolean('mrc_deleted')->default(false)->index();
            $table->timestamp('mrc_deleted_at')->nullable()->index();
            $table->timestamp('mrc_created_at')->nullable();
            $table->timestamp('mrc_updated_at')->nullable();

            $table->unique(['mrc_clave', 'mrc_deleted'], 'uk_marca_clave_activo');
            $table->unique(['mrc_nombre', 'mrc_deleted'], 'uk_marca_nombre_activo');
            $table->index(['mrc_deleted', 'mrc_estatus']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_marcas_mrc');
    }
};
