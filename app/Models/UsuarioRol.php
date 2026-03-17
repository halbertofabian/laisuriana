<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsuarioRol extends Model
{
    public const CREATED_AT = 'url_created_at';
    public const UPDATED_AT = 'url_updated_at';

    protected $table = 'tbl_usuario_roles_url';
    protected $primaryKey = 'url_id';

    protected $fillable = [
        'url_usr_id',
        'url_rol_id',
        'url_estatus',
        'url_deleted',
        'url_deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'url_deleted' => 'boolean',
            'url_deleted_at' => 'datetime',
        ];
    }
}
