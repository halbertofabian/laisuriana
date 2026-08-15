<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComisionVendedor extends Model
{
    public const CREATED_AT = 'cve_created_at';

    public const UPDATED_AT = 'cve_updated_at';

    protected $table = 'tbl_comision_vendedores_cve';

    protected $primaryKey = 'cve_id';

    protected $fillable = [
        'cve_usr_id', 'cve_cgr_id', 'cve_numero', 'cve_estatus',
        'cve_created_by_usr_id', 'cve_updated_by_usr_id',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'cve_usr_id', 'usr_id');
    }

    public function grupo()
    {
        return $this->belongsTo(ComisionGrupo::class, 'cve_cgr_id', 'cgr_id');
    }
}
