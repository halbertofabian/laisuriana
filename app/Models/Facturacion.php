<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Facturacion extends Model
{
    public const CREATED_AT = 'fac_created_at';

    public const UPDATED_AT = 'fac_updated_at';

    protected $table = 'tbl_facturacion_fac';

    protected $primaryKey = 'fac_id';

    protected $guarded = ['fac_id'];

    protected function casts(): array
    {
        return [
            'fac_original' => 'array', 'fac_partidas' => 'array', 'fac_documento' => 'array',
            'fac_total_original' => 'decimal:2', 'fac_total' => 'decimal:2',
            'fac_diferencia' => 'decimal:2', 'fac_emitida_at' => 'datetime',
        ];
    }
}
