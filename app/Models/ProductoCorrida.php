<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoCorrida extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'prc_created_at';
    public const UPDATED_AT = 'prc_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'prc_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'prc_deleted_at';

    protected $table = 'tbl_producto_corridas_prc';
    protected $primaryKey = 'prc_id';

    protected $fillable = [
        'prc_prd_id',
        'prc_atr_id',
        'prc_nombre',
        'prc_orden',
        'prc_precio_base',
        'prc_costo_base',
        'prc_stock_minimo',
        'prc_stock_maximo',
        'prc_estatus',
        'prc_created_by_usr_id',
        'prc_updated_by_usr_id',
    ];

    protected function casts(): array
    {
        return [
            'prc_precio_base' => 'decimal:2',
            'prc_costo_base' => 'decimal:2',
            'prc_stock_minimo' => 'integer',
            'prc_stock_maximo' => 'integer',
        ];
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'prc_prd_id', 'prd_id');
    }

    public function atributo()
    {
        return $this->belongsTo(Atributo::class, 'prc_atr_id', 'atr_id');
    }

    public function valores()
    {
        return $this->belongsToMany(ValorAtributo::class, 'tbl_producto_corrida_valores_pcv', 'pcv_prc_id', 'pcv_vat_id')
            ->wherePivot('pcv_deleted', false)
            ->wherePivotNull('pcv_deleted_at')
            ->wherePivot('pcv_estatus', 'activo');
    }
}

