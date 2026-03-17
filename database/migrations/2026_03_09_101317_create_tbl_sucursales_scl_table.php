<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_sucursales_scl', function (Blueprint $table) {
            $table->bigIncrements('scl_id');
            $table->string('scl_nombre', 120);
            $table->string('scl_clave', 40)->unique();
            $table->string('scl_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('scl_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('scl_updated_by_usr_id')->nullable()->index();
            $table->boolean('scl_deleted')->default(false)->index();
            $table->timestamp('scl_deleted_at')->nullable()->index();
            $table->timestamp('scl_created_at')->nullable();
            $table->timestamp('scl_updated_at')->nullable();

            $table->index(['scl_deleted', 'scl_estatus']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_sucursales_scl');
    }
};
