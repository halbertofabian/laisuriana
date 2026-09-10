<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Model;

class ComisionV2Departamento extends Model
{
    use HasLogicalDeletion;

    public const CREATED_AT = 'cmd_created_at';
    public const UPDATED_AT = 'cmd_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'cmd_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'cmd_deleted_at';

    protected $table = 'tbl_comision_v2_departamentos_cmd';
    protected $primaryKey = 'cmd_id';
    protected $fillable = ['cmd_clave', 'cmd_nombre', 'cmd_estatus', 'cmd_created_by_usr_id', 'cmd_updated_by_usr_id'];

    public function lineas()
    {
        return $this->belongsToMany(Linea::class, 'tbl_comision_v2_departamento_lineas_cdl', 'cdl_cmd_id', 'cdl_lna_id')
            ->withTimestamps('cdl_created_at', 'cdl_updated_at');
    }
}
