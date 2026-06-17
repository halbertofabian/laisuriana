<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_descripciones_dsc', function (Blueprint $table) {
            $table->bigIncrements('dsc_id');
            $table->string('dsc_nombre', 120);
            $table->string('dsc_clave', 40);
            $table->string('dsc_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('dsc_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('dsc_updated_by_usr_id')->nullable()->index();
            $table->boolean('dsc_deleted')->default(false)->index();
            $table->timestamp('dsc_deleted_at')->nullable()->index();
            $table->timestamp('dsc_created_at')->nullable();
            $table->timestamp('dsc_updated_at')->nullable();

            $table->unique(['dsc_clave', 'dsc_deleted'], 'uk_dsc_cla_del');
            $table->unique(['dsc_nombre', 'dsc_deleted'], 'uk_dsc_nom_del');
            $table->index(['dsc_deleted', 'dsc_estatus'], 'idx_dsc_del_est');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_descripciones_dsc');
    }
};
