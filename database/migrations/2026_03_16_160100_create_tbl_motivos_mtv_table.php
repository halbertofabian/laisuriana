<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_motivos_mtv', function (Blueprint $table) {
            $table->bigIncrements('mtv_id');
            $table->string('mtv_nombre', 120);
            $table->string('mtv_clave', 40);
            $table->string('mtv_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('mtv_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('mtv_updated_by_usr_id')->nullable()->index();
            $table->boolean('mtv_deleted')->default(false)->index();
            $table->timestamp('mtv_deleted_at')->nullable()->index();
            $table->timestamp('mtv_created_at')->nullable();
            $table->timestamp('mtv_updated_at')->nullable();

            $table->unique(['mtv_clave', 'mtv_deleted'], 'uk_mtv_cla_del');
            $table->unique(['mtv_nombre', 'mtv_deleted'], 'uk_mtv_nom_del');
            $table->index(['mtv_deleted', 'mtv_estatus'], 'idx_mtv_del_est');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_motivos_mtv');
    }
};
