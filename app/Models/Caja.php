<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Caja extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'caj_created_at';
    public const UPDATED_AT = 'caj_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'caj_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'caj_deleted_at';

    protected $table = 'tbl_cajas_caj';
    protected $primaryKey = 'caj_id';

    protected $fillable = [
        'caj_scl_id',
        'caj_alm_id',
        'caj_nombre',
        'caj_clave',
        'caj_estatus',
        'caj_created_by_usr_id',
        'caj_updated_by_usr_id',
    ];

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'caj_scl_id', 'scl_id');
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'caj_alm_id', 'alm_id');
    }

    public function usuarios()
    {
        return $this->belongsToMany(Usuario::class, 'tbl_caja_usuarios_cju', 'cju_caj_id', 'cju_usr_id')
            ->withPivot(['cju_estatus', 'cju_deleted', 'cju_deleted_at'])
            ->wherePivot('cju_deleted', false)
            ->wherePivotNull('cju_deleted_at')
            ->wherePivot('cju_estatus', 'activo');
    }
}
