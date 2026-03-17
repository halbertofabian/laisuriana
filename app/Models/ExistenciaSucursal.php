<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExistenciaSucursal extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'exs_created_at';
    public const UPDATED_AT = 'exs_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'exs_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'exs_deleted_at';

    protected $table = 'tbl_existencias_sucursal_exs';
    protected $primaryKey = 'exs_id';

    protected $fillable = [
        'exs_psk_id',
        'exs_scl_id',
        'exs_existencia',
        'exs_estatus',
        'exs_created_by_usr_id',
        'exs_updated_by_usr_id',
    ];

    protected function casts(): array
    {
        return [
            'exs_existencia' => 'decimal:2',
        ];
    }

    public function sku()
    {
        return $this->belongsTo(ProductoSku::class, 'exs_psk_id', 'psk_id');
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'exs_scl_id', 'scl_id');
    }
}
