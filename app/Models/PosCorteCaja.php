<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosCorteCaja extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'pco_created_at';
    public const UPDATED_AT = 'pco_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'pco_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'pco_deleted_at';

    protected $table = 'tbl_pos_cortes_pco';
    protected $primaryKey = 'pco_id';

    protected $fillable = [
        'pco_folio',
        'pco_cse_id',
        'pco_caj_id',
        'pco_scl_id',
        'pco_usr_cajero_id',
        'pco_usr_autorizo_id',
        'pco_usr_apertura_id',
        'pco_abierta_at',
        'pco_cerrada_at',
        'pco_efectivo_esperado',
        'pco_efectivo_reportado',
        'pco_diferencia',
        'pco_total_ventas',
        'pco_total_retiros',
        'pco_total_gastos',
        'pco_resumen_ventas',
        'pco_resumen_metodos_pago',
        'pco_observaciones',
        'pco_estado',
        'pco_created_by_usr_id',
        'pco_updated_by_usr_id',
    ];

    protected function casts(): array
    {
        return [
            'pco_abierta_at' => 'datetime',
            'pco_cerrada_at' => 'datetime',
            'pco_efectivo_esperado' => 'decimal:2',
            'pco_efectivo_reportado' => 'decimal:2',
            'pco_diferencia' => 'decimal:2',
            'pco_total_ventas' => 'decimal:2',
            'pco_total_retiros' => 'decimal:2',
            'pco_total_gastos' => 'decimal:2',
            'pco_resumen_ventas' => 'array',
            'pco_resumen_metodos_pago' => 'array',
        ];
    }

    public function sesion()
    {
        return $this->belongsTo(CajaSesion::class, 'pco_cse_id', 'cse_id');
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class, 'pco_caj_id', 'caj_id');
    }

    public function cajero()
    {
        return $this->belongsTo(Usuario::class, 'pco_usr_cajero_id', 'usr_id');
    }

    public function autorizadoPor()
    {
        return $this->belongsTo(Usuario::class, 'pco_usr_autorizo_id', 'usr_id');
    }

    public function aperturaUsuario()
    {
        return $this->belongsTo(Usuario::class, 'pco_usr_apertura_id', 'usr_id');
    }

    public function denominaciones()
    {
        return $this->hasMany(PosCorteCajaDenominacion::class, 'pdn_pco_id', 'pco_id')
            ->orderByRaw("
                CASE pdn_clave
                    WHEN '1000' THEN 1
                    WHEN '500' THEN 2
                    WHEN '200' THEN 3
                    WHEN '100' THEN 4
                    WHEN '50' THEN 5
                    WHEN '20' THEN 6
                    WHEN 'cambio' THEN 7
                    ELSE 99
                END
            ");
    }
}
