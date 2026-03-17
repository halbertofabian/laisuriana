<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permiso extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'prm_created_at';
    public const UPDATED_AT = 'prm_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'prm_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'prm_deleted_at';

    protected $table = 'tbl_permisos_prm';
    protected $primaryKey = 'prm_id';

    protected $fillable = [
        'prm_clave',
        'prm_descripcion',
        'prm_modulo',
        'prm_estatus',
        'prm_created_by_usr_id',
        'prm_updated_by_usr_id',
    ];
}
