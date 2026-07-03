<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbl_recepcion_mercancia_detalle_rmd')) {
            Schema::create('tbl_recepcion_mercancia_detalle_rmd', function (Blueprint $table) {
                $table->bigIncrements('rmd_id');
                $table->unsignedBigInteger('rmd_rme_id');
                $table->unsignedBigInteger('rmd_prd_id')->nullable();
                $table->unsignedBigInteger('rmd_psk_id');
                $table->decimal('rmd_cantidad', 14, 2)->default(0);
                $table->decimal('rmd_precio_unitario', 14, 2)->nullable();
                $table->json('rmd_payload')->nullable();
                $table->unsignedBigInteger('rmd_created_by_usr_id')->nullable();
                $table->unsignedBigInteger('rmd_updated_by_usr_id')->nullable();
                $table->boolean('rmd_deleted')->default(false)->index();
                $table->timestamp('rmd_deleted_at')->nullable()->index();
                $table->timestamp('rmd_created_at')->nullable();
                $table->timestamp('rmd_updated_at')->nullable();
            });
        }

        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            return;
        }

        $schema = DB::getDatabaseName();
        $hasIndex = function (string $indexName) use ($schema): bool {
            return DB::table('information_schema.statistics')
                ->where('table_schema', $schema)
                ->where('table_name', 'tbl_recepcion_mercancia_detalle_rmd')
                ->where('index_name', $indexName)
                ->exists();
        };
        $hasForeign = function (string $constraintName) use ($schema): bool {
            return DB::table('information_schema.table_constraints')
                ->where('table_schema', $schema)
                ->where('table_name', 'tbl_recepcion_mercancia_detalle_rmd')
                ->where('constraint_type', 'FOREIGN KEY')
                ->where('constraint_name', $constraintName)
                ->exists();
        };

        Schema::table('tbl_recepcion_mercancia_detalle_rmd', function (Blueprint $table) use ($hasIndex, $hasForeign) {
            if (!$hasIndex('idx_rmd_prd')) {
                $table->index('rmd_prd_id', 'idx_rmd_prd');
            }
            if (!$hasIndex('idx_rmd_cre_usr')) {
                $table->index('rmd_created_by_usr_id', 'idx_rmd_cre_usr');
            }
            if (!$hasIndex('idx_rmd_upd_usr')) {
                $table->index('rmd_updated_by_usr_id', 'idx_rmd_upd_usr');
            }
            if (!$hasIndex('uk_rmd_rme_psk_deleted')) {
                $table->unique(['rmd_rme_id', 'rmd_psk_id', 'rmd_deleted'], 'uk_rmd_rme_psk_deleted');
            }

            if (!$hasForeign('fk_rmd_rme')) {
                $table->foreign('rmd_rme_id', 'fk_rmd_rme')->references('rme_id')->on('tbl_recepciones_mercancia_rme');
            }
            if (!$hasForeign('fk_rmd_prd')) {
                $table->foreign('rmd_prd_id', 'fk_rmd_prd')->references('prd_id')->on('tbl_productos_prd');
            }
            if (!$hasForeign('fk_rmd_psk')) {
                $table->foreign('rmd_psk_id', 'fk_rmd_psk')->references('psk_id')->on('tbl_producto_skus_psk');
            }
            if (!$hasForeign('fk_rmd_cre_usr')) {
                $table->foreign('rmd_created_by_usr_id', 'fk_rmd_cre_usr')->references('usr_id')->on('tbl_usuarios_usr');
            }
            if (!$hasForeign('fk_rmd_upd_usr')) {
                $table->foreign('rmd_updated_by_usr_id', 'fk_rmd_upd_usr')->references('usr_id')->on('tbl_usuarios_usr');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_recepcion_mercancia_detalle_rmd');
    }
};
