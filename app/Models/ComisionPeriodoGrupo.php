<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComisionPeriodoGrupo extends Model
{
    public const CREATED_AT = 'cpg_created_at';

    public const UPDATED_AT = 'cpg_updated_at';

    protected $table = 'tbl_comision_periodo_grupos_cpg';

    protected $primaryKey = 'cpg_id';

    protected $fillable = [
        'cpg_cpe_id', 'cpg_cgr_id', 'cpg_vendedores_promedio', 'cpg_incremento_meta',
        'cpg_ventas_grupo', 'cpg_ventas_sin_atencion', 'cpg_base_meta', 'cpg_meta_individual',
    ];

    protected function casts(): array
    {
        return [
            'cpg_vendedores_promedio' => 'decimal:2',
            'cpg_incremento_meta' => 'decimal:2',
            'cpg_ventas_grupo' => 'decimal:2',
            'cpg_ventas_sin_atencion' => 'decimal:2',
            'cpg_base_meta' => 'decimal:2',
            'cpg_meta_individual' => 'decimal:2',
        ];
    }

    public function grupo()
    {
        return $this->belongsTo(ComisionGrupo::class, 'cpg_cgr_id', 'cgr_id');
    }
}
