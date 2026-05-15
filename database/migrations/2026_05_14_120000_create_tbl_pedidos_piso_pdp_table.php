<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_pedidos_piso_pdp', function (Blueprint $table) {
            $table->bigIncrements('pdp_id');
            $table->string('pdp_folio', 50)->unique();
            $table->unsignedBigInteger('pdp_scl_id');
            $table->unsignedBigInteger('pdp_alm_id');
            $table->unsignedBigInteger('pdp_usr_id');
            $table->string('pdp_estatus', 20)->default('pendiente_cobro')->index();
            $table->decimal('pdp_subtotal', 14, 2)->default(0);
            $table->decimal('pdp_total', 14, 2)->default(0);
            $table->text('pdp_observaciones')->nullable();
            $table->timestamp('pdp_fecha')->nullable()->index();
            $table->unsignedBigInteger('pdp_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('pdp_updated_by_usr_id')->nullable()->index();
            $table->boolean('pdp_deleted')->default(false)->index();
            $table->timestamp('pdp_deleted_at')->nullable()->index();
            $table->timestamp('pdp_created_at')->nullable();
            $table->timestamp('pdp_updated_at')->nullable();

            $table->foreign('pdp_scl_id')->references('scl_id')->on('tbl_sucursales_scl');
            $table->foreign('pdp_alm_id')->references('alm_id')->on('tbl_almacenes_alm');
            $table->foreign('pdp_usr_id')->references('usr_id')->on('tbl_usuarios_usr');

            $table->index(['pdp_scl_id', 'pdp_alm_id', 'pdp_estatus'], 'idx_pdp_scl_alm_est');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_pedidos_piso_pdp');
    }
};
