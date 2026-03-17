<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_tipos_almacen_tal', function (Blueprint $table) {
            $table->bigIncrements('tal_id');
            $table->string('tal_nombre', 80)->unique();
            $table->string('tal_clave', 40)->unique();
            $table->string('tal_descripcion', 220)->nullable();
            $table->string('tal_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('tal_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('tal_updated_by_usr_id')->nullable()->index();
            $table->boolean('tal_deleted')->default(false)->index();
            $table->timestamp('tal_deleted_at')->nullable()->index();
            $table->timestamp('tal_created_at')->nullable();
            $table->timestamp('tal_updated_at')->nullable();

            $table->index(['tal_deleted', 'tal_estatus']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_tipos_almacen_tal');
    }
};
