<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Almacen extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'alm_created_at';
    public const UPDATED_AT = 'alm_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'alm_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'alm_deleted_at';

    protected $table = 'tbl_almacenes_alm';
    protected $primaryKey = 'alm_id';

    protected $fillable = [
        'alm_scl_id',
        'alm_tal_id',
        'alm_nombre',
        'alm_clave',
        'alm_estatus',
        'alm_created_by_usr_id',
        'alm_updated_by_usr_id',
    ];

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'alm_scl_id', 'scl_id');
    }

    public function tipo()
    {
        return $this->belongsTo(TipoAlmacen::class, 'alm_tal_id', 'tal_id');
    }

    public function existenciasInventario()
    {
        return $this->hasMany(ExistenciaAlmacen::class, 'exa_alm_id', 'alm_id');
    }

    public function minimosInventario()
    {
        return $this->hasMany(MinimoInventario::class, 'mni_alm_id', 'alm_id');
    }

    public function movimientosInventario()
    {
        return $this->hasMany(MovimientoInventario::class, 'min_alm_id', 'alm_id');
    }
}
