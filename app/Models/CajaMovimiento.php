<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CajaMovimiento extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'cjm_created_at';
    public const UPDATED_AT = 'cjm_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'cjm_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'cjm_deleted_at';

    protected $table = 'tbl_caja_movimientos_cjm';
    protected $primaryKey = 'cjm_id';

    protected $fillable = [
        'cjm_folio',
        'cjm_cse_id',
        'cjm_caj_id',
        'cjm_scl_id',
        'cjm_usr_cajero_id',
        'cjm_usr_autorizo_id',
        'cjm_tipo',
        'cjm_monto',
        'cjm_denominaciones',
        'cjm_categoria',
        'cjm_referencia',
        'cjm_motivo',
        'cjm_estatus',
        'cjm_fecha_movimiento',
        'cjm_created_by_usr_id',
        'cjm_updated_by_usr_id',
    ];

    protected function casts(): array
    {
        return [
            'cjm_monto' => 'decimal:2',
            'cjm_denominaciones' => 'array',
            'cjm_fecha_movimiento' => 'datetime',
        ];
    }

    public function cajaSesion()
    {
        return $this->belongsTo(CajaSesion::class, 'cjm_cse_id', 'cse_id');
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class, 'cjm_caj_id', 'caj_id');
    }

    public function cajero()
    {
        return $this->belongsTo(Usuario::class, 'cjm_usr_cajero_id', 'usr_id');
    }

    public function autorizadoPor()
    {
        return $this->belongsTo(Usuario::class, 'cjm_usr_autorizo_id', 'usr_id');
    }
}
