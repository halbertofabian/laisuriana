<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Motivo extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'mtv_created_at';
    public const UPDATED_AT = 'mtv_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'mtv_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'mtv_deleted_at';

    protected $table = 'tbl_motivos_mtv';
    protected $primaryKey = 'mtv_id';

    protected $fillable = [
        'mtv_nombre',
        'mtv_clave',
        'mtv_estatus',
        'mtv_created_by_usr_id',
        'mtv_updated_by_usr_id',
    ];
}
