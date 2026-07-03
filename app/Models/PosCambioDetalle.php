<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosCambioDetalle extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'pcd_created_at';
    public const UPDATED_AT = 'pcd_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'pcd_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'pcd_deleted_at';

    protected $table = 'tbl_pos_cambios_detalle_pcd';
    protected $primaryKey = 'pcd_id';

    protected $fillable = [
        'pcd_psv_id',
        'pcd_psv_origen_id',
        'pcd_pvd_origen_id',
        'pcd_psk_id',
        'pcd_alm_id',
        'pcd_cantidad',
        'pcd_precio_unitario',
        'pcd_importe_credito',
        'pcd_condicion',
        'pcd_created_by_usr_id',
        'pcd_updated_by_usr_id',
    ];

    protected function casts(): array
    {
        return [
            'pcd_cantidad' => 'decimal:2',
            'pcd_precio_unitario' => 'decimal:2',
            'pcd_importe_credito' => 'decimal:2',
        ];
    }

    public function ventaCambio()
    {
        return $this->belongsTo(PosVenta::class, 'pcd_psv_id', 'psv_id');
    }

    public function ventaOrigen()
    {
        return $this->belongsTo(PosVenta::class, 'pcd_psv_origen_id', 'psv_id');
    }

    public function detalleOrigen()
    {
        return $this->belongsTo(PosVentaDetalle::class, 'pcd_pvd_origen_id', 'pvd_id');
    }

    public function sku()
    {
        return $this->belongsTo(ProductoSku::class, 'pcd_psk_id', 'psk_id');
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'pcd_alm_id', 'alm_id');
    }
}
