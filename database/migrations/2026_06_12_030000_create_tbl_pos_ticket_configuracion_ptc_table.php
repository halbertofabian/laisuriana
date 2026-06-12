<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_pos_ticket_configuracion_ptc', function (Blueprint $table) {
            $table->bigIncrements('ptc_id');
            $table->string('ptc_logo_path', 255)->nullable();
            $table->text('ptc_texto_encabezado')->nullable();
            $table->text('ptc_texto_pie')->nullable();
            $table->unsignedBigInteger('ptc_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('ptc_updated_by_usr_id')->nullable()->index();
            $table->timestamp('ptc_created_at')->nullable();
            $table->timestamp('ptc_updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_pos_ticket_configuracion_ptc');
    }
};
