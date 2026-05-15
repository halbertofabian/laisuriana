<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tbl_clientes_cli', function (Blueprint $table): void {
            $table->bigIncrements('cli_id');
            $table->string('cli_nombre', 120);
            $table->string('cli_apellido_paterno', 120)->nullable();
            $table->string('cli_apellido_materno', 120)->nullable();
            $table->string('cli_razon_social', 180)->nullable();
            $table->date('cli_fecha_nacimiento')->nullable();

            $table->string('cli_telefono', 25)->nullable();
            $table->string('cli_whatsapp', 25)->nullable();
            $table->string('cli_email', 140)->nullable();

            $table->string('cli_rfc', 20)->nullable();
            $table->string('cli_curp', 25)->nullable();
            $table->string('cli_ine', 30)->nullable();

            $table->string('cli_cp', 10)->nullable();
            $table->string('cli_colonia', 150)->nullable();
            $table->string('cli_tipo_asentamiento', 80)->nullable();
            $table->string('cli_municipio', 120)->nullable();
            $table->string('cli_estado', 120)->nullable();
            $table->string('cli_ciudad', 120)->nullable();
            $table->string('cli_calle', 180)->nullable();
            $table->string('cli_num_ext', 30)->nullable();
            $table->string('cli_num_int', 30)->nullable();
            $table->text('cli_referencias')->nullable();

            $table->string('cli_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('cli_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('cli_updated_by_usr_id')->nullable()->index();
            $table->boolean('cli_deleted')->default(false)->index();
            $table->timestamp('cli_deleted_at')->nullable()->index();
            $table->timestamp('cli_created_at')->nullable();
            $table->timestamp('cli_updated_at')->nullable();

            $table->index(['cli_nombre', 'cli_apellido_paterno', 'cli_apellido_materno'], 'idx_cliente_nombre');
            $table->index(['cli_cp', 'cli_colonia'], 'idx_cliente_cp_colonia');
            $table->unique(['cli_rfc', 'cli_deleted'], 'uk_cliente_rfc_activo');
            $table->unique(['cli_curp', 'cli_deleted'], 'uk_cliente_curp_activo');
            $table->unique(['cli_email', 'cli_deleted'], 'uk_cliente_email_activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_clientes_cli');
    }
};

