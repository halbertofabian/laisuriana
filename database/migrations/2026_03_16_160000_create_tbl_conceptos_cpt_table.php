<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_conceptos_cpt', function (Blueprint $table) {
            $table->bigIncrements('cpt_id');
            $table->string('cpt_nombre', 120);
            $table->string('cpt_clave', 40);
            $table->string('cpt_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('cpt_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('cpt_updated_by_usr_id')->nullable()->index();
            $table->boolean('cpt_deleted')->default(false)->index();
            $table->timestamp('cpt_deleted_at')->nullable()->index();
            $table->timestamp('cpt_created_at')->nullable();
            $table->timestamp('cpt_updated_at')->nullable();

            $table->unique(['cpt_clave', 'cpt_deleted'], 'uk_cpt_cla_del');
            $table->unique(['cpt_nombre', 'cpt_deleted'], 'uk_cpt_nom_del');
            $table->index(['cpt_deleted', 'cpt_estatus'], 'idx_cpt_del_est');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_conceptos_cpt');
    }
};
