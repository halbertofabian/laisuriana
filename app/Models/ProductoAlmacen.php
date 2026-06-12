<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoAlmacen extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'pra_created_at';
    public const UPDATED_AT = 'pra_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'pra_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'pra_deleted_at';

    protected $table = 'tbl_producto_almacenes_pra';
    protected $primaryKey = 'pra_id';

    protected $fillable = [
        'pra_prd_id',
        'pra_alm_id',
        'pra_created_by_usr_id',
        'pra_updated_by_usr_id',
    ];
}
