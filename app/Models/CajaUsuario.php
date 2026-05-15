<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CajaUsuario extends Model
{
    public const CREATED_AT = 'cju_created_at';
    public const UPDATED_AT = 'cju_updated_at';

    protected $table = 'tbl_caja_usuarios_cju';
    protected $primaryKey = 'cju_id';

    protected $fillable = [
        'cju_caj_id',
        'cju_usr_id',
        'cju_estatus',
        'cju_deleted',
        'cju_deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'cju_deleted' => 'boolean',
            'cju_deleted_at' => 'datetime',
        ];
    }
}
