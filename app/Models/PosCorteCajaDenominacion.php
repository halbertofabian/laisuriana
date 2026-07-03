<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosCorteCajaDenominacion extends Model
{
    use HasFactory;

    public const CREATED_AT = 'pdn_created_at';
    public const UPDATED_AT = 'pdn_updated_at';

    protected $table = 'tbl_pos_corte_denominaciones_pdn';
    protected $primaryKey = 'pdn_id';

    protected $fillable = [
        'pdn_pco_id',
        'pdn_clave',
        'pdn_etiqueta',
        'pdn_tipo',
        'pdn_cantidad_piezas',
        'pdn_monto_unitario',
        'pdn_monto',
    ];

    protected function casts(): array
    {
        return [
            'pdn_monto_unitario' => 'decimal:2',
            'pdn_monto' => 'decimal:2',
        ];
    }

    public function corte()
    {
        return $this->belongsTo(PosCorteCaja::class, 'pdn_pco_id', 'pco_id');
    }
}
