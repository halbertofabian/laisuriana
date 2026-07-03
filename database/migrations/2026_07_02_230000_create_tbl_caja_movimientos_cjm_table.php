<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_caja_movimientos_cjm', function (Blueprint $table) {
            $table->bigIncrements('cjm_id');
            $table->string('cjm_folio', 50)->unique();
            $table->unsignedBigInteger('cjm_cse_id');
            $table->unsignedBigInteger('cjm_caj_id');
            $table->unsignedBigInteger('cjm_scl_id');
            $table->unsignedBigInteger('cjm_usr_cajero_id');
            $table->unsignedBigInteger('cjm_usr_autorizo_id')->nullable();
            $table->string('cjm_tipo', 20)->index();
            $table->decimal('cjm_monto', 14, 2)->default(0);
            $table->string('cjm_categoria', 120)->nullable();
            $table->string('cjm_referencia', 180)->nullable();
            $table->text('cjm_motivo')->nullable();
            $table->string('cjm_estatus', 20)->default('registrado')->index();
            $table->timestamp('cjm_fecha_movimiento')->index();
            $table->unsignedBigInteger('cjm_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('cjm_updated_by_usr_id')->nullable()->index();
            $table->boolean('cjm_deleted')->default(false)->index();
            $table->timestamp('cjm_deleted_at')->nullable()->index();
            $table->timestamp('cjm_created_at')->nullable();
            $table->timestamp('cjm_updated_at')->nullable();

            $table->foreign('cjm_cse_id')->references('cse_id')->on('tbl_caja_sesiones_cse');
            $table->foreign('cjm_caj_id')->references('caj_id')->on('tbl_cajas_caj');
            $table->foreign('cjm_scl_id')->references('scl_id')->on('tbl_sucursales_scl');
            $table->foreign('cjm_usr_cajero_id')->references('usr_id')->on('tbl_usuarios_usr');
            $table->foreign('cjm_usr_autorizo_id')->references('usr_id')->on('tbl_usuarios_usr');

            $table->index(['cjm_cse_id', 'cjm_tipo', 'cjm_estatus'], 'idx_cjm_sesion_tipo_estatus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_caja_movimientos_cjm');
    }
};
