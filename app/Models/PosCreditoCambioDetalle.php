<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosCreditoCambioDetalle extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'pcdv_created_at';
    public const UPDATED_AT = 'pcdv_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'pcdv_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'pcdv_deleted_at';

    protected $table = 'tbl_pos_creditos_cambio_detalle_pcdv';
    protected $primaryKey = 'pcdv_id';

    protected $fillable = [
        'pcdv_pcc_id',
        'pcdv_psv_origen_id',
        'pcdv_pvd_origen_id',
        'pcdv_psk_id',
        'pcdv_alm_id',
        'pcdv_cantidad',
        'pcdv_precio_unitario',
        'pcdv_importe_credito',
        'pcdv_condicion',
        'pcdv_created_by_usr_id',
        'pcdv_updated_by_usr_id',
    ];

    protected function casts(): array
    {
        return [
            'pcdv_cantidad' => 'decimal:2',
            'pcdv_precio_unitario' => 'decimal:2',
            'pcdv_importe_credito' => 'decimal:2',
        ];
    }

    public function credito()
    {
        return $this->belongsTo(PosCreditoCambio::class, 'pcdv_pcc_id', 'pcc_id');
    }

    public function ventaOrigen()
    {
        return $this->belongsTo(PosVenta::class, 'pcdv_psv_origen_id', 'psv_id');
    }

    public function detalleOrigen()
    {
        return $this->belongsTo(PosVentaDetalle::class, 'pcdv_pvd_origen_id', 'pvd_id');
    }

    public function sku()
    {
        return $this->belongsTo(ProductoSku::class, 'pcdv_psk_id', 'psk_id');
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'pcdv_alm_id', 'alm_id');
    }
}
