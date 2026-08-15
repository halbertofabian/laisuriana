<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComisionPeriodo extends Model
{
    public const CREATED_AT = 'cpe_created_at';

    public const UPDATED_AT = 'cpe_updated_at';

    protected $table = 'tbl_comision_periodos_cpe';

    protected $primaryKey = 'cpe_id';

    protected $fillable = [
        'cpe_scl_id', 'cpe_periodo', 'cpe_factor_comisionable', 'cpe_tasa_general',
        'cpe_cumplimiento_minimo', 'cpe_estatus', 'cpe_calculado_at',
        'cpe_calculado_by_usr_id', 'cpe_cerrado_at', 'cpe_cerrado_by_usr_id',
        'cpe_created_by_usr_id', 'cpe_updated_by_usr_id',
    ];

    protected function casts(): array
    {
        return [
            'cpe_periodo' => 'date',
            'cpe_factor_comisionable' => 'decimal:2',
            'cpe_tasa_general' => 'decimal:4',
            'cpe_cumplimiento_minimo' => 'decimal:2',
            'cpe_calculado_at' => 'datetime',
            'cpe_cerrado_at' => 'datetime',
        ];
    }

    public function almacenes()
    {
        return $this->belongsToMany(Almacen::class, 'tbl_comision_periodo_almacenes_cpa', 'cpa_cpe_id', 'cpa_alm_id')
            ->withTimestamps('cpa_created_at', 'cpa_updated_at');
    }

    public function configuracionesGrupo()
    {
        return $this->hasMany(ComisionPeriodoGrupo::class, 'cpg_cpe_id', 'cpe_id');
    }

    public function ajustes()
    {
        return $this->hasMany(ComisionAjusteVendedor::class, 'cav_cpe_id', 'cpe_id');
    }

    public function resultados()
    {
        return $this->hasMany(ComisionResultado::class, 'crs_cpe_id', 'cpe_id');
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'cpe_scl_id', 'scl_id');
    }
}
