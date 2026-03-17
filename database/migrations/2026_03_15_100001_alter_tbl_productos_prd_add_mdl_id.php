<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_productos_prd', function (Blueprint $table) {
            $table->unsignedBigInteger('prd_mdl_id')
                ->nullable()
                ->after('prd_mrc_id')
                ->index();

            $table->foreign('prd_mdl_id')
                ->references('mdl_id')
                ->on('tbl_modelos_mdl')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tbl_productos_prd', function (Blueprint $table) {
            $table->dropForeign(['prd_mdl_id']);
            $table->dropColumn('prd_mdl_id');
        });
    }
};
