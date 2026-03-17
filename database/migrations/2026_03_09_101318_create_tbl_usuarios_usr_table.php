<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_usuarios_usr', function (Blueprint $table) {
            $table->bigIncrements('usr_id');
            $table->string('usr_usuario', 60)->unique();
            $table->string('usr_password');
            $table->string('usr_nombre', 160);
            $table->string('usr_email', 160)->nullable()->index();
            $table->string('usr_estatus', 20)->default('activo')->index();
            $table->rememberToken('usr_remember_token');
            $table->unsignedBigInteger('usr_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('usr_updated_by_usr_id')->nullable()->index();
            $table->boolean('usr_deleted')->default(false)->index();
            $table->timestamp('usr_deleted_at')->nullable()->index();
            $table->timestamp('usr_created_at')->nullable();
            $table->timestamp('usr_updated_at')->nullable();

            $table->index(['usr_deleted', 'usr_estatus']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_usuarios_usr');
    }
};
