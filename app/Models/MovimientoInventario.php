<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoInventario extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'min_created_at';
    public const UPDATED_AT = 'min_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'min_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'min_deleted_at';

    protected $table = 'tbl_movimientos_inventario_min';
    protected $primaryKey = 'min_id';

    protected $fillable = [
        'min_folio',
        'min_tmi_id',
        'min_psk_id',
        'min_scl_id',
        'min_alm_id',
        'min_prv_id',
        'min_rme_id',
        'min_mtv_id',
        'min_origen_min_id',
        'min_reversa_de_min_id',
        'min_documento_tipo',
        'min_documento_referencia',
        'min_descuento_tipo',
        'min_descuento_valor',
        'min_flete_total',
        'min_cantidad',
        'min_precio_unitario',
        'min_subtotal_linea',
        'min_descuento_linea',
        'min_flete_linea',
        'min_iva_porcentaje',
        'min_iva_linea',
        'min_total_linea',
        'min_signo',
        'min_existencia_antes',
        'min_existencia_despues',
        'min_motivo_texto',
        'min_observaciones',
        'min_estatus',
        'min_es_reversa',
        'min_fecha_movimiento',
        'min_fecha_emision',
        'min_cancelado_at',
        'min_cancelado_by_usr_id',
        'min_cancelacion_motivo',
        'min_created_by_usr_id',
        'min_updated_by_usr_id',
    ];

    protected function casts(): array
    {
        return [
            'min_cantidad' => 'decimal:2',
            'min_precio_unitario' => 'decimal:2',
            'min_subtotal_linea' => 'decimal:2',
            'min_descuento_linea' => 'decimal:2',
            'min_flete_linea' => 'decimal:2',
            'min_iva_porcentaje' => 'decimal:2',
            'min_iva_linea' => 'decimal:2',
            'min_total_linea' => 'decimal:2',
            'min_descuento_valor' => 'decimal:2',
            'min_flete_total' => 'decimal:2',
            'min_existencia_antes' => 'decimal:2',
            'min_existencia_despues' => 'decimal:2',
            'min_es_reversa' => 'boolean',
            'min_fecha_movimiento' => 'datetime',
            'min_fecha_emision' => 'datetime',
            'min_cancelado_at' => 'datetime',
        ];
    }

    public function tipoMovimiento()
    {
        return $this->belongsTo(TipoMovimientoInventario::class, 'min_tmi_id', 'tmi_id');
    }

    public function sku()
    {
        return $this->belongsTo(ProductoSku::class, 'min_psk_id', 'psk_id');
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'min_scl_id', 'scl_id');
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'min_alm_id', 'alm_id');
    }

    public function motivo()
    {
        return $this->belongsTo(Motivo::class, 'min_mtv_id', 'mtv_id');
    }

    public function recepcionMercancia()
    {
        return $this->belongsTo(RecepcionMercancia::class, 'min_rme_id', 'rme_id');
    }

    public function origen()
    {
        return $this->belongsTo(self::class, 'min_origen_min_id', 'min_id');
    }

    public function reversaDe()
    {
        return $this->belongsTo(self::class, 'min_reversa_de_min_id', 'min_id');
    }
}
