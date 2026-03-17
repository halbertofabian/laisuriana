<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoAtributo extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'pat_created_at';
    public const UPDATED_AT = 'pat_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'pat_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'pat_deleted_at';

    protected $table = 'tbl_producto_atributos_pat';
    protected $primaryKey = 'pat_id';

    protected $fillable = [
        'pat_prd_id',
        'pat_atr_id',
        'pat_estatus',
        'pat_created_by_usr_id',
        'pat_updated_by_usr_id',
    ];
}
