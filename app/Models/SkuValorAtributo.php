<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkuValorAtributo extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'sva_created_at';
    public const UPDATED_AT = 'sva_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'sva_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'sva_deleted_at';

    protected $table = 'tbl_sku_valores_atributo_sva';
    protected $primaryKey = 'sva_id';

    protected $fillable = [
        'sva_psk_id',
        'sva_vat_id',
        'sva_estatus',
        'sva_created_by_usr_id',
        'sva_updated_by_usr_id',
    ];
}
