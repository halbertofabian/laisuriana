<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComisionResultadoDetalle extends Model
{
    public const CREATED_AT = 'crd_created_at';

    public const UPDATED_AT = 'crd_updated_at';

    protected $table = 'tbl_comision_resultado_detalles_crd';

    protected $primaryKey = 'crd_id';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'crd_venta_bruta' => 'decimal:2',
            'crd_descuentos' => 'decimal:2',
            'crd_devoluciones' => 'decimal:2',
            'crd_venta_neta' => 'decimal:2',
        ];
    }
}
