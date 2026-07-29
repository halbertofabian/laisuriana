<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosCreditoCambioAplicacion extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'pca_created_at';
    public const UPDATED_AT = 'pca_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'pca_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'pca_deleted_at';

    protected $table = 'tbl_pos_creditos_cambio_aplicaciones_pca';
    protected $primaryKey = 'pca_id';

    protected $fillable = [
        'pca_pcc_id',
        'pca_psv_id',
        'pca_cse_id',
        'pca_caj_id',
        'pca_scl_id',
        'pca_usr_id',
        'pca_monto_aplicado',
        'pca_fecha_aplicacion',
        'pca_created_by_usr_id',
        'pca_updated_by_usr_id',
    ];

    protected function casts(): array
    {
        return [
            'pca_monto_aplicado' => 'decimal:2',
            'pca_fecha_aplicacion' => 'datetime',
        ];
    }

    public function credito()
    {
        return $this->belongsTo(PosCreditoCambio::class, 'pca_pcc_id', 'pcc_id');
    }

    public function venta()
    {
        return $this->belongsTo(PosVenta::class, 'pca_psv_id', 'psv_id');
    }
}
