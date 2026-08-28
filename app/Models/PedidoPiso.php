<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoPiso extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'pdp_created_at';
    public const UPDATED_AT = 'pdp_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'pdp_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'pdp_deleted_at';

    protected $table = 'tbl_pedidos_piso_pdp';
    protected $primaryKey = 'pdp_id';

    protected $fillable = [
        'pdp_folio',
        'pdp_mobile_request_id',
        'pdp_scl_id',
        'pdp_alm_id',
        'pdp_usr_id',
        'pdp_cli_id',
        'pdp_estatus',
        'pdp_subtotal',
        'pdp_total',
        'pdp_observaciones',
        'pdp_fecha',
        'pdp_created_by_usr_id',
        'pdp_updated_by_usr_id',
    ];

    protected function casts(): array
    {
        return [
            'pdp_subtotal' => 'decimal:2',
            'pdp_total' => 'decimal:2',
            'pdp_fecha' => 'datetime',
        ];
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'pdp_scl_id', 'scl_id');
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'pdp_alm_id', 'alm_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'pdp_usr_id', 'usr_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'pdp_cli_id', 'cli_id');
    }

    public function detalle()
    {
        return $this->hasMany(PedidoPisoDetalle::class, 'ppd_pdp_id', 'pdp_id')
            ->where('ppd_deleted', false)
            ->whereNull('ppd_deleted_at');
    }

    public function detalleConEliminados()
    {
        return $this->hasMany(PedidoPisoDetalle::class, 'ppd_pdp_id', 'pdp_id')
            ->withDeleted();
    }
}
