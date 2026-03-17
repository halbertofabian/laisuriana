<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_categorias_ctg', function (Blueprint $table) {
            $table->bigIncrements('ctg_id');
            $table->string('ctg_nombre', 120);
            $table->string('ctg_clave', 40);
            $table->string('ctg_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('ctg_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('ctg_updated_by_usr_id')->nullable()->index();
            $table->boolean('ctg_deleted')->default(false)->index();
            $table->timestamp('ctg_deleted_at')->nullable()->index();
            $table->timestamp('ctg_created_at')->nullable();
            $table->timestamp('ctg_updated_at')->nullable();

            $table->unique(['ctg_clave', 'ctg_deleted'], 'uk_categoria_clave_activo');
            $table->unique(['ctg_nombre', 'ctg_deleted'], 'uk_categoria_nombre_activo');
            $table->index(['ctg_deleted', 'ctg_estatus']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_categorias_ctg');
    }
};
