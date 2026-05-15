<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use HasLogicalDeletion;

    public const CREATED_AT = 'usr_created_at';
    public const UPDATED_AT = 'usr_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'usr_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'usr_deleted_at';

    protected $table = 'tbl_usuarios_usr';
    protected $primaryKey = 'usr_id';

    protected $fillable = [
        'usr_usuario',
        'usr_password',
        'usr_nombre',
        'usr_email',
        'usr_estatus',
        'usr_created_by_usr_id',
        'usr_updated_by_usr_id',
    ];

    protected $hidden = [
        'usr_password',
        'usr_remember_token',
    ];

    protected function casts(): array
    {
        return [
            'usr_deleted' => 'boolean',
            'usr_created_at' => 'datetime',
            'usr_updated_at' => 'datetime',
            'usr_deleted_at' => 'datetime',
        ];
    }

    public function getAuthIdentifierName(): string
    {
        return 'usr_id';
    }

    public function getAuthPassword(): string
    {
        return $this->usr_password;
    }

    public function getRememberTokenName(): string
    {
        return 'usr_remember_token';
    }

    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'tbl_usuario_roles_url', 'url_usr_id', 'url_rol_id')
            ->wherePivot('url_deleted', false)
            ->wherePivotNull('url_deleted_at')
            ->wherePivot('url_estatus', 'activo');
    }

    public function sucursales()
    {
        return $this->belongsToMany(Sucursal::class, 'tbl_usuario_sucursales_usc', 'usc_usr_id', 'usc_scl_id')
            ->wherePivot('usc_deleted', false)
            ->wherePivotNull('usc_deleted_at')
            ->wherePivot('usc_estatus', 'activo');
    }

    public function cajas()
    {
        return $this->belongsToMany(Caja::class, 'tbl_caja_usuarios_cju', 'cju_usr_id', 'cju_caj_id')
            ->wherePivot('cju_deleted', false)
            ->wherePivotNull('cju_deleted_at')
            ->wherePivot('cju_estatus', 'activo');
    }

    public function tienePermiso(string $permisoClave): bool
    {
        return Permiso::query()
            ->join('tbl_rol_permisos_rpm as rpm', 'rpm.rpm_prm_id', '=', 'tbl_permisos_prm.prm_id')
            ->join('tbl_usuario_roles_url as url', 'url.url_rol_id', '=', 'rpm.rpm_rol_id')
            ->where('url.url_usr_id', $this->usr_id)
            ->where('tbl_permisos_prm.prm_clave', $permisoClave)
            ->where('tbl_permisos_prm.prm_estatus', 'activo')
            ->where('tbl_permisos_prm.prm_deleted', false)
            ->whereNull('tbl_permisos_prm.prm_deleted_at')
            ->where('rpm.rpm_estatus', 'activo')
            ->where('rpm.rpm_deleted', false)
            ->whereNull('rpm.rpm_deleted_at')
            ->where('url.url_estatus', 'activo')
            ->where('url.url_deleted', false)
            ->whereNull('url.url_deleted_at')
            ->exists();
    }
}
