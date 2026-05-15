<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoCorridaValor extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'pcv_created_at';
    public const UPDATED_AT = 'pcv_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'pcv_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'pcv_deleted_at';

    protected $table = 'tbl_producto_corrida_valores_pcv';
    protected $primaryKey = 'pcv_id';

    protected $fillable = [
        'pcv_prc_id',
        'pcv_vat_id',
        'pcv_estatus',
        'pcv_created_by_usr_id',
        'pcv_updated_by_usr_id',
    ];

    public function corrida()
    {
        return $this->belongsTo(ProductoCorrida::class, 'pcv_prc_id', 'prc_id');
    }

    public function valorAtributo()
    {
        return $this->belongsTo(ValorAtributo::class, 'pcv_vat_id', 'vat_id');
    }
}

