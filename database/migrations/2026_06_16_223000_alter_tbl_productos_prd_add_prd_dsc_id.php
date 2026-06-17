<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_productos_prd', function (Blueprint $table) {
            $table->unsignedBigInteger('prd_dsc_id')->nullable()->after('prd_ctg_id');
            $table->foreign('prd_dsc_id')
                ->references('dsc_id')
                ->on('tbl_descripciones_dsc');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_productos_prd', function (Blueprint $table) {
            $table->dropForeign(['prd_dsc_id']);
            $table->dropColumn('prd_dsc_id');
        });
    }
};
