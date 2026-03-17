<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_productos_prd', function (Blueprint $table) {
            $table->decimal('prd_costo', 12, 2)->default(0)->after('prd_precio_base');
            $table->string('prd_codigo_barras', 80)->nullable()->after('prd_codigo');
            $table->unique(['prd_codigo_barras', 'prd_deleted'], 'uk_prd_bar_del');
        });

        Schema::table('tbl_producto_skus_psk', function (Blueprint $table) {
            $table->string('psk_codigo_barras', 80)->nullable()->after('psk_codigo');
            $table->unique(['psk_codigo_barras', 'psk_deleted'], 'uk_psk_bar_del');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_producto_skus_psk', function (Blueprint $table) {
            $table->dropUnique('uk_psk_bar_del');
            $table->dropColumn('psk_codigo_barras');
        });

        Schema::table('tbl_productos_prd', function (Blueprint $table) {
            $table->dropUnique('uk_prd_bar_del');
            $table->dropColumn([
                'prd_costo',
                'prd_codigo_barras',
            ]);
        });
    }
};
