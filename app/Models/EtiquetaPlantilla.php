<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Model;

class EtiquetaPlantilla extends Model
{
    use HasLogicalDeletion;
    public const CREATED_AT = 'etp_created_at'; public const UPDATED_AT = 'etp_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'etp_deleted'; public const LOGICAL_DELETED_AT_COLUMN = 'etp_deleted_at';
    protected $table = 'tbl_etiqueta_plantillas_etp'; protected $primaryKey = 'etp_id';
    protected $fillable = ['etp_nombre','etp_descripcion','etp_campos','etp_estatus','etp_created_by_usr_id','etp_updated_by_usr_id'];
    protected function casts(): array { return ['etp_campos'=>'array']; }
}
