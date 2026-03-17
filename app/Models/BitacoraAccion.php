<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Model;

class BitacoraAccion extends Model
{
    use HasLogicalDeletion;

    public const CREATED_AT = 'bac_created_at';
    public const UPDATED_AT = 'bac_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'bac_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'bac_deleted_at';

    protected $table = 'tbl_bitacora_acciones_bac';
    protected $primaryKey = 'bac_id';

    protected $fillable = [
        'bac_usr_id',
        'bac_scl_id',
        'bac_accion',
        'bac_entidad',
        'bac_entidad_id',
        'bac_payload',
        'bac_ip',
        'bac_user_agent',
    ];

    protected function casts(): array
    {
        return [
            'bac_payload' => 'array',
        ];
    }
}
