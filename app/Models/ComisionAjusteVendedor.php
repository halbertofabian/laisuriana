<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComisionAjusteVendedor extends Model
{
    public const CREATED_AT = 'cav_created_at';

    public const UPDATED_AT = 'cav_updated_at';

    protected $table = 'tbl_comision_ajustes_vendedor_cav';

    protected $primaryKey = 'cav_id';

    protected $fillable = [
        'cav_cpe_id', 'cav_cve_id', 'cav_ajuste_tasa', 'cav_tasa_final', 'cav_bono', 'cav_motivo',
    ];

    protected function casts(): array
    {
        return [
            'cav_ajuste_tasa' => 'decimal:4',
            'cav_tasa_final' => 'decimal:4',
            'cav_bono' => 'decimal:2',
        ];
    }
}
