<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosCreditoCambio extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'pcc_created_at';
    public const UPDATED_AT = 'pcc_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'pcc_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'pcc_deleted_at';
    public const LOGICAL_DELETION_UNIQUE_COLUMNS = ['pcc_folio' => 50];

    protected $table = 'tbl_pos_creditos_cambio_pcc';
    protected $primaryKey = 'pcc_id';

    protected $fillable = [
        'pcc_folio',
        'pcc_cse_id',
        'pcc_caj_id',
        'pcc_scl_id',
        'pcc_alm_id',
        'pcc_usr_id',
        'pcc_cli_id',
        'pcc_psv_origen_id',
        'pcc_estatus',
        'pcc_total_credito',
        'pcc_saldo_disponible',
        'pcc_notas',
        'pcc_fecha_generado',
        'pcc_cancelado_at',
        'pcc_cancelado_by_usr_id',
        'pcc_cancelacion_motivo',
        'pcc_created_by_usr_id',
        'pcc_updated_by_usr_id',
    ];

    protected function casts(): array
    {
        return [
            'pcc_total_credito' => 'decimal:2',
            'pcc_saldo_disponible' => 'decimal:2',
            'pcc_fecha_generado' => 'datetime',
            'pcc_cancelado_at' => 'datetime',
        ];
    }

    public function detalle()
    {
        return $this->hasMany(PosCreditoCambioDetalle::class, 'pcdv_pcc_id', 'pcc_id')
            ->where('pcdv_deleted', false)
            ->whereNull('pcdv_deleted_at');
    }

    public function aplicaciones()
    {
        return $this->hasMany(PosCreditoCambioAplicacion::class, 'pca_pcc_id', 'pcc_id')
            ->where('pca_deleted', false)
            ->whereNull('pca_deleted_at');
    }

    public function ventaOrigen()
    {
        return $this->belongsTo(PosVenta::class, 'pcc_psv_origen_id', 'psv_id');
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'pcc_alm_id', 'alm_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'pcc_cli_id', 'cli_id');
    }
}
