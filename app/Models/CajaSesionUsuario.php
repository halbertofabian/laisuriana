<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CajaSesionUsuario extends Model
{
    public const CREATED_AT = 'csu_created_at';
    public const UPDATED_AT = 'csu_updated_at';

    protected $table = 'tbl_caja_sesion_usuarios_csu';
    protected $primaryKey = 'csu_id';

    protected $fillable = [
        'csu_cse_id',
        'csu_usr_id',
        'csu_ingreso_at',
        'csu_salida_at',
        'csu_estatus',
    ];

    protected function casts(): array
    {
        return [
            'csu_ingreso_at' => 'datetime',
            'csu_salida_at' => 'datetime',
        ];
    }
}
