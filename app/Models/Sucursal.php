<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sucursal extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'scl_created_at';
    public const UPDATED_AT = 'scl_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'scl_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'scl_deleted_at';

    protected $table = 'tbl_sucursales_scl';
    protected $primaryKey = 'scl_id';

    protected $fillable = [
        'scl_nombre',
        'scl_clave',
        'scl_estatus',
        'scl_created_by_usr_id',
        'scl_updated_by_usr_id',
    ];

    public function almacenes()
    {
        return $this->hasMany(Almacen::class, 'alm_scl_id', 'scl_id');
    }

    public function existenciasSku()
    {
        return $this->hasMany(ExistenciaSucursal::class, 'exs_scl_id', 'scl_id');
    }

    public function existenciasAlmacen()
    {
        return $this->hasMany(ExistenciaAlmacen::class, 'exa_scl_id', 'scl_id');
    }

    public function movimientosInventario()
    {
        return $this->hasMany(MovimientoInventario::class, 'min_scl_id', 'scl_id');
    }
}
