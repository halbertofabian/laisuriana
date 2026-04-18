<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_movimientos_inventario_min', function (Blueprint $table) {
            $table->unsignedBigInteger('min_prv_id')->nullable()->after('min_alm_id')->index();
            $table->timestamp('min_fecha_emision')->nullable()->after('min_fecha_movimiento')->index();
            $table->decimal('min_precio_unitario', 14, 2)->nullable()->after('min_cantidad');
            $table->decimal('min_subtotal_linea', 14, 2)->nullable()->after('min_precio_unitario');
            $table->decimal('min_descuento_linea', 14, 2)->nullable()->after('min_subtotal_linea');
            $table->decimal('min_flete_linea', 14, 2)->nullable()->after('min_descuento_linea');
            $table->decimal('min_iva_porcentaje', 6, 2)->nullable()->after('min_flete_linea');
            $table->decimal('min_iva_linea', 14, 2)->nullable()->after('min_iva_porcentaje');
            $table->decimal('min_total_linea', 14, 2)->nullable()->after('min_iva_linea');
            $table->string('min_descuento_tipo', 20)->nullable()->after('min_documento_referencia');
            $table->decimal('min_descuento_valor', 14, 2)->nullable()->after('min_descuento_tipo');
            $table->decimal('min_flete_total', 14, 2)->nullable()->after('min_descuento_valor');
            $table->text('min_observaciones')->nullable()->after('min_motivo_texto');

            $table->foreign('min_prv_id')->references('prv_id')->on('tbl_proveedores_prv');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_movimientos_inventario_min', function (Blueprint $table) {
            $table->dropForeign(['min_prv_id']);
            $table->dropColumn([
                'min_prv_id',
                'min_fecha_emision',
                'min_precio_unitario',
                'min_subtotal_linea',
                'min_descuento_linea',
                'min_flete_linea',
                'min_iva_porcentaje',
                'min_iva_linea',
                'min_total_linea',
                'min_descuento_tipo',
                'min_descuento_valor',
                'min_flete_total',
                'min_observaciones',
            ]);
        });
    }
};
