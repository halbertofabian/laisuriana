<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComisionV2Periodo extends Model
{
    public const CREATED_AT = 'cmp_created_at';
    public const UPDATED_AT = 'cmp_updated_at';

    protected $table = 'tbl_comision_v2_periodos_cmp';
    protected $primaryKey = 'cmp_id';
    protected $fillable = [
        'cmp_scl_id', 'cmp_periodo', 'cmp_factor_comisionable', 'cmp_estatus', 'cmp_ultimo_motivo_cambio',
        'cmp_aprobado_at', 'cmp_aprobado_by_usr_id', 'cmp_cerrado_at', 'cmp_cerrado_by_usr_id',
        'cmp_created_by_usr_id', 'cmp_updated_by_usr_id',
    ];

    protected function casts(): array
    {
        return [
            'cmp_periodo' => 'date',
            'cmp_factor_comisionable' => 'decimal:2',
            'cmp_aprobado_at' => 'datetime',
            'cmp_cerrado_at' => 'datetime',
        ];
    }

    public function almacenes()
    {
        return $this->belongsToMany(Almacen::class, 'tbl_comision_v2_periodo_almacenes_cma', 'cma_cmp_id', 'cma_alm_id')
            ->withTimestamps('cma_created_at', 'cma_updated_at');
    }

    public function departamentos()
    {
        return $this->hasMany(ComisionV2PeriodoDepartamento::class, 'cpd_cmp_id', 'cmp_id');
    }

    public function participantes()
    {
        return $this->hasMany(ComisionV2Participante::class, 'cpt_cmp_id', 'cmp_id');
    }

    public function resultados()
    {
        return $this->hasMany(ComisionV2Resultado::class, 'cmr_cmp_id', 'cmp_id');
    }
}
