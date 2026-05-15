<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_caja_sesiones_cse', function (Blueprint $table) {
            $table->bigIncrements('cse_id');
            $table->unsignedBigInteger('cse_caj_id');
            $table->unsignedBigInteger('cse_scl_id');
            $table->unsignedBigInteger('cse_usr_apertura_id');
            $table->decimal('cse_monto_apertura', 12, 2)->default(0);
            $table->timestamp('cse_abierta_at')->index();
            $table->timestamp('cse_cerrada_at')->nullable()->index();
            $table->string('cse_estatus', 20)->default('activa')->index();
            $table->timestamp('cse_created_at')->nullable();
            $table->timestamp('cse_updated_at')->nullable();

            $table->foreign('cse_caj_id')->references('caj_id')->on('tbl_cajas_caj');
            $table->foreign('cse_scl_id')->references('scl_id')->on('tbl_sucursales_scl');
            $table->foreign('cse_usr_apertura_id')->references('usr_id')->on('tbl_usuarios_usr');
            $table->index(['cse_caj_id', 'cse_estatus']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_caja_sesiones_cse');
    }
};
