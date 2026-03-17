<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolPermiso extends Model
{
    public const CREATED_AT = 'rpm_created_at';
    public const UPDATED_AT = 'rpm_updated_at';

    protected $table = 'tbl_rol_permisos_rpm';
    protected $primaryKey = 'rpm_id';

    protected $fillable = [
        'rpm_rol_id',
        'rpm_prm_id',
        'rpm_estatus',
        'rpm_deleted',
        'rpm_deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'rpm_deleted' => 'boolean',
            'rpm_deleted_at' => 'datetime',
        ];
    }
}
