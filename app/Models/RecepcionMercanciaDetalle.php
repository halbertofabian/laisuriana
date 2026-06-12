<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecepcionMercanciaDetalle extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'rmd_created_at';
    public const UPDATED_AT = 'rmd_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'rmd_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'rmd_deleted_at';

    protected $table = 'tbl_recepcion_mercancia_detalle_rmd';
    protected $primaryKey = 'rmd_id';

    protected $fillable = [
        'rmd_rme_id',
        'rmd_prd_id',
        'rmd_psk_id',
        'rmd_cantidad',
        'rmd_precio_unitario',
        'rmd_payload',
        'rmd_created_by_usr_id',
        'rmd_updated_by_usr_id',
    ];

    protected function casts(): array
    {
        return [
            'rmd_cantidad' => 'decimal:2',
            'rmd_precio_unitario' => 'decimal:2',
            'rmd_payload' => 'array',
        ];
    }

    public function recepcion()
    {
        return $this->belongsTo(RecepcionMercancia::class, 'rmd_rme_id', 'rme_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'rmd_prd_id', 'prd_id');
    }

    public function sku()
    {
        return $this->belongsTo(ProductoSku::class, 'rmd_psk_id', 'psk_id');
    }
}
