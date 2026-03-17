<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Linea extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'lna_created_at';
    public const UPDATED_AT = 'lna_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'lna_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'lna_deleted_at';

    protected $table = 'tbl_lineas_lna';
    protected $primaryKey = 'lna_id';

    protected $fillable = [
        'lna_nombre',
        'lna_clave',
        'lna_estatus',
        'lna_created_by_usr_id',
        'lna_updated_by_usr_id',
    ];
}
