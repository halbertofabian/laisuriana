<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecepcionMercancia extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'rme_created_at';
    public const UPDATED_AT = 'rme_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'rme_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'rme_deleted_at';
    public const ESTADO_BORRADOR = 'borrador';
    public const ESTADO_FINALIZADO = 'finalizado';
    public const ESTADO_CANCELADO = 'cancelado';

    protected $table = 'tbl_recepciones_mercancia_rme';
    protected $primaryKey = 'rme_id';

    protected $fillable = [
        'rme_folio',
        'rme_scl_id',
        'rme_alm_id',
        'rme_prv_id',
        'rme_dominante_atr_id',
        'rme_documento_tipo',
        'rme_documento_referencia',
        'rme_descuento_tipo',
        'rme_descuento_valor',
        'rme_flete_total',
        'rme_iva_porcentaje',
        'rme_fecha_captura',
        'rme_fecha_emision',
        'rme_motivo_texto',
        'rme_observaciones',
        'rme_payload',
        'rme_estado',
        'rme_confirmado_at',
        'rme_confirmado_by_usr_id',
        'rme_cancelado_at',
        'rme_cancelado_by_usr_id',
        'rme_cancelacion_motivo',
        'rme_created_by_usr_id',
        'rme_updated_by_usr_id',
    ];

    protected function casts(): array
    {
        return [
            'rme_descuento_valor' => 'decimal:2',
            'rme_flete_total' => 'decimal:2',
            'rme_iva_porcentaje' => 'decimal:2',
            'rme_fecha_captura' => 'datetime',
            'rme_fecha_emision' => 'datetime',
            'rme_confirmado_at' => 'datetime',
            'rme_cancelado_at' => 'datetime',
            'rme_payload' => 'array',
        ];
    }

    public function detalle()
    {
        return $this->hasMany(RecepcionMercanciaDetalle::class, 'rmd_rme_id', 'rme_id');
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'rme_scl_id', 'scl_id');
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'rme_alm_id', 'alm_id');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'rme_prv_id', 'prv_id');
    }
}
