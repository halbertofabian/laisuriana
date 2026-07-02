<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoPisoDetalle extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'ppd_created_at';
    public const UPDATED_AT = 'ppd_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'ppd_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'ppd_deleted_at';

    protected $table = 'tbl_pedido_piso_detalle_ppd';
    protected $primaryKey = 'ppd_id';

    protected $fillable = [
        'ppd_pdp_id',
        'ppd_psk_id',
        'ppd_cantidad',
        'ppd_precio_unitario',
        'ppd_descuento_tipo',
        'ppd_descuento_valor',
        'ppd_descuento_importe',
        'ppd_descuento_cantidad',
        'ppd_importe',
        'ppd_total_linea',
        'ppd_usr_id',
        'ppd_created_by_usr_id',
        'ppd_updated_by_usr_id',
    ];

    protected function casts(): array
    {
        return [
            'ppd_cantidad' => 'decimal:2',
            'ppd_precio_unitario' => 'decimal:2',
            'ppd_descuento_valor' => 'decimal:2',
            'ppd_descuento_importe' => 'decimal:2',
            'ppd_descuento_cantidad' => 'decimal:2',
            'ppd_importe' => 'decimal:2',
            'ppd_total_linea' => 'decimal:2',
        ];
    }

    public function pedido()
    {
        return $this->belongsTo(PedidoPiso::class, 'ppd_pdp_id', 'pdp_id');
    }

    public function sku()
    {
        return $this->belongsTo(ProductoSku::class, 'ppd_psk_id', 'psk_id');
    }

    public function capturista()
    {
        return $this->belongsTo(Usuario::class, 'ppd_usr_id', 'usr_id');
    }
}
