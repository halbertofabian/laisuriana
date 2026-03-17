<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_usuario_roles_url', function (Blueprint $table) {
            $table->bigIncrements('url_id');
            $table->unsignedBigInteger('url_usr_id');
            $table->unsignedBigInteger('url_rol_id');
            $table->string('url_estatus', 20)->default('activo')->index();
            $table->boolean('url_deleted')->default(false)->index();
            $table->timestamp('url_deleted_at')->nullable()->index();
            $table->timestamp('url_created_at')->nullable();
            $table->timestamp('url_updated_at')->nullable();

            $table->foreign('url_usr_id')->references('usr_id')->on('tbl_usuarios_usr');
            $table->foreign('url_rol_id')->references('rol_id')->on('tbl_roles_rol');
            $table->unique(['url_usr_id', 'url_rol_id', 'url_deleted'], 'uk_usuario_rol_activo');
            $table->index(['url_usr_id', 'url_deleted']);
            $table->index(['url_rol_id', 'url_deleted']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_usuario_roles_url');
    }
};
