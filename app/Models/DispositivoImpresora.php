<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DispositivoImpresora extends Model
{
    use HasFactory;

    public const CREATED_AT = 'dip_created_at';
    public const UPDATED_AT = 'dip_updated_at';

    protected $table = 'tbl_dispositivo_impresoras_dip';
    protected $primaryKey = 'dip_id';

    protected $fillable = [
        'dip_device_uid',
        'dip_nombre_dispositivo',
        'dip_tipo_conexion',
        'dip_nombre_impresora',
        'dip_host',
        'dip_puerto',
        'dip_controlador',
        'dip_agent_url',
        'dip_created_by_usr_id',
        'dip_updated_by_usr_id',
    ];

    protected function casts(): array
    {
        return [
            'dip_puerto' => 'integer',
            'dip_created_at' => 'datetime',
            'dip_updated_at' => 'datetime',
        ];
    }
}
