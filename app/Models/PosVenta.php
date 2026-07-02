<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosVenta extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'psv_created_at';
    public const UPDATED_AT = 'psv_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'psv_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'psv_deleted_at';

    protected $table = 'tbl_pos_ventas_psv';
    protected $primaryKey = 'psv_id';

    protected $fillable = [
        'psv_folio',
        'psv_cse_id',
        'psv_caj_id',
        'psv_scl_id',
        'psv_alm_id',
        'psv_usr_id',
        'psv_cli_id',
        'psv_pdp_id',
        'psv_estatus',
        'psv_subtotal',
        'psv_descuento',
        'psv_total',
        'psv_metodo_pago',
        'psv_pago_detalle',
        'psv_pagado',
        'psv_cambio',
        'psv_notas',
        'psv_fecha_cobro',
        'psv_created_by_usr_id',
        'psv_updated_by_usr_id',
    ];

    protected function casts(): array
    {
        return [
            'psv_subtotal' => 'decimal:2',
            'psv_descuento' => 'decimal:2',
            'psv_total' => 'decimal:2',
            'psv_pago_detalle' => 'array',
            'psv_pagado' => 'decimal:2',
            'psv_cambio' => 'decimal:2',
            'psv_fecha_cobro' => 'datetime',
        ];
    }

    public function detalle()
    {
        return $this->hasMany(PosVentaDetalle::class, 'pvd_psv_id', 'psv_id')
            ->where('pvd_deleted', false)
            ->whereNull('pvd_deleted_at');
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'psv_alm_id', 'alm_id');
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class, 'psv_caj_id', 'caj_id');
    }

    public function cajaSesion()
    {
        return $this->belongsTo(CajaSesion::class, 'psv_cse_id', 'cse_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'psv_cli_id', 'cli_id');
    }

    public function vendedor()
    {
        return $this->belongsTo(Usuario::class, 'psv_usr_id', 'usr_id');
    }
}
