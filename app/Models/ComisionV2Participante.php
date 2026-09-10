<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComisionV2Participante extends Model
{
    public const CREATED_AT = 'cpt_created_at';
    public const UPDATED_AT = 'cpt_updated_at';

    protected $table = 'tbl_comision_v2_participantes_cpt';
    protected $primaryKey = 'cpt_id';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'cpt_meta_individual' => 'decimal:2',
            'cpt_tasa_comision' => 'decimal:4',
        ];
    }

    public function periodo()
    {
        return $this->belongsTo(ComisionV2Periodo::class, 'cpt_cmp_id', 'cmp_id');
    }

    public function departamentoPeriodo()
    {
        return $this->belongsTo(ComisionV2PeriodoDepartamento::class, 'cpt_cpd_id', 'cpd_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'cpt_usr_id', 'usr_id');
    }
}
