<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComisionV2PeriodoDepartamento extends Model
{
    public const CREATED_AT = 'cpd_created_at';
    public const UPDATED_AT = 'cpd_updated_at';

    protected $table = 'tbl_comision_v2_periodo_departamentos_cpd';
    protected $primaryKey = 'cpd_id';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'cpd_periodo_referencia' => 'date',
            'cpd_ventas_historicas' => 'decimal:2',
            'cpd_autoservicio_historico' => 'decimal:2',
            'cpd_base_historica' => 'decimal:2',
            'cpd_incremento_meta' => 'decimal:2',
            'cpd_meta_sugerida' => 'decimal:2',
            'cpd_meta_comun' => 'decimal:2',
        ];
    }

    public function periodo()
    {
        return $this->belongsTo(ComisionV2Periodo::class, 'cpd_cmp_id', 'cmp_id');
    }

    public function departamento()
    {
        return $this->belongsTo(ComisionV2Departamento::class, 'cpd_cmd_id', 'cmd_id');
    }

    public function participantes()
    {
        return $this->hasMany(ComisionV2Participante::class, 'cpt_cpd_id', 'cpd_id');
    }
}
