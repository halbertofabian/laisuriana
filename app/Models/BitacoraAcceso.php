<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Model;

class BitacoraAcceso extends Model
{
    use HasLogicalDeletion;

    public const CREATED_AT = 'bac_created_at';
    public const UPDATED_AT = 'bac_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'bac_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'bac_deleted_at';

    protected $table = 'tbl_bitacora_accesos_bac';
    protected $primaryKey = 'bac_id';

    protected $fillable = [
        'bac_usr_id',
        'bac_usuario_intentado',
        'bac_resultado',
        'bac_motivo',
        'bac_ip',
        'bac_user_agent',
    ];
}
