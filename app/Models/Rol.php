<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'rol_created_at';
    public const UPDATED_AT = 'rol_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'rol_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'rol_deleted_at';

    protected $table = 'tbl_roles_rol';
    protected $primaryKey = 'rol_id';

    protected $fillable = [
        'rol_nombre',
        'rol_descripcion',
        'rol_estatus',
        'rol_created_by_usr_id',
        'rol_updated_by_usr_id',
    ];

    public function permisos()
    {
        return $this->belongsToMany(Permiso::class, 'tbl_rol_permisos_rpm', 'rpm_rol_id', 'rpm_prm_id')
            ->wherePivot('rpm_deleted', false)
            ->wherePivotNull('rpm_deleted_at')
            ->wherePivot('rpm_estatus', 'activo');
    }
}
