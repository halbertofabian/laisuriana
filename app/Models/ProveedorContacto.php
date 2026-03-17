<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProveedorContacto extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'prc_created_at';
    public const UPDATED_AT = 'prc_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'prc_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'prc_deleted_at';

    protected $table = 'tbl_proveedor_contactos_prc';
    protected $primaryKey = 'prc_id';

    protected $fillable = [
        'prc_prv_id',
        'prc_numero',
        'prc_orden',
        'prc_estatus',
        'prc_created_by_usr_id',
        'prc_updated_by_usr_id',
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'prc_prv_id', 'prv_id');
    }
}
