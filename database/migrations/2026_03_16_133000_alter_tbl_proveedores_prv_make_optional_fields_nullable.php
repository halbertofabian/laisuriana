<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbl_proveedores_prv')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE tbl_proveedores_prv MODIFY prv_nombre_asesor_ventas VARCHAR(180) NULL');
        DB::statement('ALTER TABLE tbl_proveedores_prv MODIFY prv_categoria VARCHAR(120) NULL');
        DB::statement('ALTER TABLE tbl_proveedores_prv MODIFY prv_razon_social VARCHAR(180) NULL');
        DB::statement('ALTER TABLE tbl_proveedores_prv MODIFY prv_rfc VARCHAR(13) NULL');
    }

    public function down(): void
    {
        // Intencionalmente sin reversa automática para evitar pérdida o coerción de datos.
    }
};
