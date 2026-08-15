<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComisionResultado extends Model
{
    public const CREATED_AT = 'crs_created_at';

    public const UPDATED_AT = 'crs_updated_at';

    protected $table = 'tbl_comision_resultados_crs';

    protected $primaryKey = 'crs_id';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'crs_ventas_totales' => 'decimal:2',
            'crs_meta' => 'decimal:2',
            'crs_cumplimiento' => 'decimal:2',
            'crs_factor_comisionable' => 'decimal:2',
            'crs_base_comisionable' => 'decimal:2',
            'crs_tasa_general' => 'decimal:4',
            'crs_ajuste_tasa' => 'decimal:4',
            'crs_tasa_final' => 'decimal:4',
            'crs_comision' => 'decimal:2',
            'crs_bono' => 'decimal:2',
            'crs_total_pagar' => 'decimal:2',
        ];
    }

    public function vendedor()
    {
        return $this->belongsTo(ComisionVendedor::class, 'crs_cve_id', 'cve_id');
    }

    public function detalles()
    {
        return $this->hasMany(ComisionResultadoDetalle::class, 'crd_crs_id', 'crs_id');
    }
}
