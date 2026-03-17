<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_productos_prd', function (Blueprint $table) {
            $table->decimal('prd_precio_base', 12, 2)->default(0)->after('prd_descripcion');
            $table->unsignedInteger('prd_stock_minimo')->default(0)->after('prd_precio_base');
            $table->unsignedInteger('prd_stock_maximo')->default(0)->after('prd_stock_minimo');
            $table->string('prd_tipo', 20)->default('simple')->after('prd_umd_id')->index();
        });

        Schema::table('tbl_producto_skus_psk', function (Blueprint $table) {
            $table->decimal('psk_precio', 12, 2)->default(0)->after('psk_nombre');
            $table->unsignedInteger('psk_stock_minimo')->default(0)->after('psk_precio');
            $table->unsignedInteger('psk_stock_maximo')->default(0)->after('psk_stock_minimo');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_producto_skus_psk', function (Blueprint $table) {
            $table->dropColumn([
                'psk_precio',
                'psk_stock_minimo',
                'psk_stock_maximo',
            ]);
        });

        Schema::table('tbl_productos_prd', function (Blueprint $table) {
            $table->dropColumn([
                'prd_precio_base',
                'prd_stock_minimo',
                'prd_stock_maximo',
                'prd_tipo',
            ]);
        });
    }
};
