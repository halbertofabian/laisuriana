<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CajaSesion extends Model
{
    use HasFactory;

    public const CREATED_AT = 'cse_created_at';
    public const UPDATED_AT = 'cse_updated_at';

    protected $table = 'tbl_caja_sesiones_cse';
    protected $primaryKey = 'cse_id';

    protected $fillable = [
        'cse_caj_id',
        'cse_scl_id',
        'cse_usr_apertura_id',
        'cse_monto_apertura',
        'cse_abierta_at',
        'cse_cerrada_at',
        'cse_estatus',
    ];

    protected function casts(): array
    {
        return [
            'cse_monto_apertura' => 'decimal:2',
            'cse_abierta_at' => 'datetime',
            'cse_cerrada_at' => 'datetime',
        ];
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class, 'cse_caj_id', 'caj_id');
    }

    public function aperturaUsuario()
    {
        return $this->belongsTo(Usuario::class, 'cse_usr_apertura_id', 'usr_id');
    }

    public function usuariosActivos()
    {
        return $this->belongsToMany(Usuario::class, 'tbl_caja_sesion_usuarios_csu', 'csu_cse_id', 'csu_usr_id')
            ->withPivot(['csu_ingreso_at', 'csu_salida_at', 'csu_estatus'])
            ->wherePivot('csu_estatus', 'activo')
            ->wherePivotNull('csu_salida_at');
    }
}
