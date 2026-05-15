<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_productos_prd', function (Blueprint $table) {
            $table->dropForeign(['prd_ctg_id']);
        });

        Schema::table('tbl_productos_prd', function (Blueprint $table) {
            $table->unsignedBigInteger('prd_ctg_id')->nullable()->change();
        });

        Schema::table('tbl_productos_prd', function (Blueprint $table) {
            $table->foreign('prd_ctg_id')
                ->references('ctg_id')
                ->on('tbl_categorias_ctg')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tbl_productos_prd', function (Blueprint $table) {
            $table->dropForeign(['prd_ctg_id']);
        });

        Schema::table('tbl_productos_prd', function (Blueprint $table) {
            $table->unsignedBigInteger('prd_ctg_id')->nullable(false)->change();
        });

        Schema::table('tbl_productos_prd', function (Blueprint $table) {
            $table->foreign('prd_ctg_id')
                ->references('ctg_id')
                ->on('tbl_categorias_ctg');
        });
    }
};
