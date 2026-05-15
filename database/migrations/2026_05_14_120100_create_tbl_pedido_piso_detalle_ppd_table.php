<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_pedido_piso_detalle_ppd', function (Blueprint $table) {
            $table->bigIncrements('ppd_id');
            $table->unsignedBigInteger('ppd_pdp_id');
            $table->unsignedBigInteger('ppd_psk_id');
            $table->decimal('ppd_cantidad', 14, 2);
            $table->decimal('ppd_precio_unitario', 14, 2)->default(0);
            $table->decimal('ppd_importe', 14, 2)->default(0);
            $table->unsignedBigInteger('ppd_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('ppd_updated_by_usr_id')->nullable()->index();
            $table->boolean('ppd_deleted')->default(false)->index();
            $table->timestamp('ppd_deleted_at')->nullable()->index();
            $table->timestamp('ppd_created_at')->nullable();
            $table->timestamp('ppd_updated_at')->nullable();

            $table->foreign('ppd_pdp_id')->references('pdp_id')->on('tbl_pedidos_piso_pdp');
            $table->foreign('ppd_psk_id')->references('psk_id')->on('tbl_producto_skus_psk');

            $table->index(['ppd_pdp_id', 'ppd_deleted'], 'idx_ppd_pdp_del');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_pedido_piso_detalle_ppd');
    }
};
