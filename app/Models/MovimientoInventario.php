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
        'min_mtv_id',
        'min_origen_min_id',
        'min_reversa_de_min_id',
        'min_documento_tipo',
        'min_documento_referencia',
        'min_cantidad',
        'min_signo',
        'min_existencia_antes',
        'min_existencia_despues',
        'min_motivo_texto',
        'min_estatus',
        'min_es_reversa',
        'min_fecha_movimiento',
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
            'min_existencia_antes' => 'decimal:2',
            'min_existencia_despues' => 'decimal:2',
            'min_es_reversa' => 'boolean',
            'min_fecha_movimiento' => 'datetime',
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

    public function origen()
    {
        return $this->belongsTo(self::class, 'min_origen_min_id', 'min_id');
    }

    public function reversaDe()
    {
        return $this->belongsTo(self::class, 'min_reversa_de_min_id', 'min_id');
    }
}
