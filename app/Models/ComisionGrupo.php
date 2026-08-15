<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Model;

class ComisionGrupo extends Model
{
    use HasLogicalDeletion;

    public const CREATED_AT = 'cgr_created_at';

    public const UPDATED_AT = 'cgr_updated_at';

    public const LOGICAL_DELETED_COLUMN = 'cgr_deleted';

    public const LOGICAL_DELETED_AT_COLUMN = 'cgr_deleted_at';

    protected $table = 'tbl_comision_grupos_cgr';

    protected $primaryKey = 'cgr_id';

    protected $fillable = [
        'cgr_clave', 'cgr_nombre', 'cgr_incremento_minimo', 'cgr_incremento_maximo',
        'cgr_estatus', 'cgr_created_by_usr_id', 'cgr_updated_by_usr_id',
    ];

    protected function casts(): array
    {
        return [
            'cgr_incremento_minimo' => 'decimal:2',
            'cgr_incremento_maximo' => 'decimal:2',
        ];
    }

    public function lineas()
    {
        return $this->belongsToMany(Linea::class, 'tbl_comision_grupo_lineas_cgl', 'cgl_cgr_id', 'cgl_lna_id')
            ->withTimestamps('cgl_created_at', 'cgl_updated_at');
    }

    public function vendedores()
    {
        return $this->hasMany(ComisionVendedor::class, 'cve_cgr_id', 'cgr_id');
    }
}
