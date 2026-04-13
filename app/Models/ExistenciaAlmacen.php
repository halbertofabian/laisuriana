<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExistenciaAlmacen extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'exa_created_at';
    public const UPDATED_AT = 'exa_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'exa_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'exa_deleted_at';

    protected $table = 'tbl_existencias_almacen_exa';
    protected $primaryKey = 'exa_id';

    protected $fillable = [
        'exa_psk_id',
        'exa_scl_id',
        'exa_alm_id',
        'exa_existencia',
        'exa_estatus',
        'exa_created_by_usr_id',
        'exa_updated_by_usr_id',
    ];

    protected function casts(): array
    {
        return [
            'exa_existencia' => 'decimal:2',
        ];
    }

    public function sku()
    {
        return $this->belongsTo(ProductoSku::class, 'exa_psk_id', 'psk_id');
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'exa_scl_id', 'scl_id');
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'exa_alm_id', 'alm_id');
    }
}
