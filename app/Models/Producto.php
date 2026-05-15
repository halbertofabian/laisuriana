<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'prd_created_at';
    public const UPDATED_AT = 'prd_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'prd_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'prd_deleted_at';

    protected $table = 'tbl_productos_prd';
    protected $primaryKey = 'prd_id';

    protected $fillable = [
        'prd_codigo',
        'prd_codigo_barras',
        'prd_clave_sat',
        'prd_nombre',
        'prd_descripcion',
        'prd_imagen_tipo',
        'prd_imagen_path',
        'prd_imagen_url',
        'prd_precio_base',
        'prd_costo',
        'prd_stock_minimo',
        'prd_stock_maximo',
        'prd_mrc_id',
        'prd_mdl_id',
        'prd_prv_id',
        'prd_lna_id',
        'prd_ctg_id',
        'prd_umd_id',
        'prd_tipo',
        'prd_estatus',
        'prd_created_by_usr_id',
        'prd_updated_by_usr_id',
    ];

    protected function casts(): array
    {
        return [
            'prd_precio_base' => 'decimal:2',
            'prd_costo' => 'decimal:2',
            'prd_stock_minimo' => 'integer',
            'prd_stock_maximo' => 'integer',
        ];
    }

    public function marca()
    {
        return $this->belongsTo(Marca::class, 'prd_mrc_id', 'mrc_id');
    }

    public function modelo()
    {
        return $this->belongsTo(ModeloProducto::class, 'prd_mdl_id', 'mdl_id');
    }

    public function linea()
    {
        return $this->belongsTo(Linea::class, 'prd_lna_id', 'lna_id');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'prd_prv_id', 'prv_id');
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'prd_ctg_id', 'ctg_id');
    }

    public function unidad()
    {
        return $this->belongsTo(UnidadMedida::class, 'prd_umd_id', 'umd_id');
    }

    public function atributos()
    {
        return $this->belongsToMany(Atributo::class, 'tbl_producto_atributos_pat', 'pat_prd_id', 'pat_atr_id')
            ->wherePivot('pat_deleted', false)
            ->wherePivotNull('pat_deleted_at')
            ->wherePivot('pat_estatus', 'activo');
    }

    public function skus()
    {
        return $this->hasMany(ProductoSku::class, 'psk_prd_id', 'prd_id');
    }

    public function corridas()
    {
        return $this->hasMany(ProductoCorrida::class, 'prc_prd_id', 'prd_id');
    }
}
