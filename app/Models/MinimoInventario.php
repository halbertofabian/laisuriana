<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MinimoInventario extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'mni_created_at';
    public const UPDATED_AT = 'mni_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'mni_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'mni_deleted_at';

    protected $table = 'tbl_minimos_inventario_mni';
    protected $primaryKey = 'mni_id';

    protected $fillable = [
        'mni_psk_id',
        'mni_scl_id',
        'mni_alm_id',
        'mni_minimo',
        'mni_estatus',
        'mni_created_by_usr_id',
        'mni_updated_by_usr_id',
    ];

    protected function casts(): array
    {
        return [
            'mni_minimo' => 'decimal:2',
        ];
    }

    public function sku()
    {
        return $this->belongsTo(ProductoSku::class, 'mni_psk_id', 'psk_id');
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'mni_scl_id', 'scl_id');
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'mni_alm_id', 'alm_id');
    }
}
