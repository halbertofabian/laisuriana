<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_producto_skus_psk', function (Blueprint $table) {
            $table->decimal('psk_costo', 12, 2)->default(0)->after('psk_precio');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_producto_skus_psk', function (Blueprint $table) {
            $table->dropColumn('psk_costo');
        });
    }
};

