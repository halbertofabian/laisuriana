<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoSku extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'psk_created_at';
    public const UPDATED_AT = 'psk_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'psk_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'psk_deleted_at';

    protected $table = 'tbl_producto_skus_psk';
    protected $primaryKey = 'psk_id';

    protected $fillable = [
        'psk_prd_id',
        'psk_codigo',
        'psk_codigo_barras',
        'psk_nombre',
        'psk_precio',
        'psk_stock_minimo',
        'psk_stock_maximo',
        'psk_estatus',
        'psk_created_by_usr_id',
        'psk_updated_by_usr_id',
    ];

    protected function casts(): array
    {
        return [
            'psk_precio' => 'decimal:2',
            'psk_stock_minimo' => 'integer',
            'psk_stock_maximo' => 'integer',
        ];
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'psk_prd_id', 'prd_id');
    }

    public function valoresAtributo()
    {
        return $this->belongsToMany(ValorAtributo::class, 'tbl_sku_valores_atributo_sva', 'sva_psk_id', 'sva_vat_id')
            ->wherePivot('sva_deleted', false)
            ->wherePivotNull('sva_deleted_at')
            ->wherePivot('sva_estatus', 'activo');
    }

    public function existenciasSucursal()
    {
        return $this->hasMany(ExistenciaSucursal::class, 'exs_psk_id', 'psk_id');
    }

    public function existenciasAlmacen()
    {
        return $this->hasMany(ExistenciaAlmacen::class, 'exa_psk_id', 'psk_id');
    }

    public function minimosInventario()
    {
        return $this->hasMany(MinimoInventario::class, 'mni_psk_id', 'psk_id');
    }

    public function movimientosInventario()
    {
        return $this->hasMany(MovimientoInventario::class, 'min_psk_id', 'psk_id');
    }
}
