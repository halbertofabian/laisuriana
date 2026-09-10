<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComisionV2Resultado extends Model
{
    public const CREATED_AT = 'cmr_created_at';
    public const UPDATED_AT = 'cmr_updated_at';

    protected $table = 'tbl_comision_v2_resultados_cmr';
    protected $primaryKey = 'cmr_id';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'cmr_ventas_netas' => 'decimal:2',
            'cmr_meta_individual' => 'decimal:2',
            'cmr_cumplimiento' => 'decimal:2',
            'cmr_factor_comisionable' => 'decimal:2',
            'cmr_base_comisionable' => 'decimal:2',
            'cmr_tasa_comision' => 'decimal:4',
            'cmr_comision' => 'decimal:2',
        ];
    }

    public function detalles()
    {
        return $this->hasMany(ComisionV2ResultadoDetalle::class, 'crx_cmr_id', 'cmr_id');
    }
}
