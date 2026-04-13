<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoMovimientoInventario extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'tmi_created_at';
    public const UPDATED_AT = 'tmi_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'tmi_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'tmi_deleted_at';

    protected $table = 'tbl_tipos_movimiento_inventario_tmi';
    protected $primaryKey = 'tmi_id';

    protected $fillable = [
        'tmi_clave',
        'tmi_nombre',
        'tmi_naturaleza',
        'tmi_clase',
        'tmi_estatus',
        'tmi_created_by_usr_id',
        'tmi_updated_by_usr_id',
    ];
}
