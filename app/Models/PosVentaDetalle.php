<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosVentaDetalle extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'pvd_created_at';
    public const UPDATED_AT = 'pvd_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'pvd_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'pvd_deleted_at';

    protected $table = 'tbl_pos_venta_detalle_pvd';
    protected $primaryKey = 'pvd_id';

    protected $fillable = [
        'pvd_psv_id',
        'pvd_psk_id',
        'pvd_alm_id',
        'pvd_cantidad',
        'pvd_precio_unitario',
        'pvd_descuento_porcentaje',
        'pvd_descuento_importe',
        'pvd_importe',
        'pvd_usr_id',
        'pvd_created_by_usr_id',
        'pvd_updated_by_usr_id',
    ];

    protected function casts(): array
    {
        return [
            'pvd_cantidad' => 'decimal:2',
            'pvd_precio_unitario' => 'decimal:2',
            'pvd_descuento_porcentaje' => 'decimal:2',
            'pvd_descuento_importe' => 'decimal:2',
            'pvd_importe' => 'decimal:2',
        ];
    }

    public function venta()
    {
        return $this->belongsTo(PosVenta::class, 'pvd_psv_id', 'psv_id');
    }

    public function sku()
    {
        return $this->belongsTo(ProductoSku::class, 'pvd_psk_id', 'psk_id');
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'pvd_alm_id', 'alm_id');
    }

    public function vendedor()
    {
        return $this->belongsTo(Usuario::class, 'pvd_usr_id', 'usr_id');
    }

    public function cambiosDevolucion()
    {
        return $this->hasMany(PosCambioDetalle::class, 'pcd_pvd_origen_id', 'pvd_id')
            ->where('pcd_deleted', false)
            ->whereNull('pcd_deleted_at');
    }
}
