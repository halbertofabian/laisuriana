<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsuarioSucursal extends Model
{
    public const CREATED_AT = 'usc_created_at';
    public const UPDATED_AT = 'usc_updated_at';

    protected $table = 'tbl_usuario_sucursales_usc';
    protected $primaryKey = 'usc_id';

    protected $fillable = [
        'usc_usr_id',
        'usc_scl_id',
        'usc_es_predeterminada',
        'usc_estatus',
        'usc_deleted',
        'usc_deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'usc_es_predeterminada' => 'boolean',
            'usc_deleted' => 'boolean',
            'usc_deleted_at' => 'datetime',
        ];
    }
}
