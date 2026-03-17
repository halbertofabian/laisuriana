<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'prv_created_at';
    public const UPDATED_AT = 'prv_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'prv_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'prv_deleted_at';

    protected $table = 'tbl_proveedores_prv';
    protected $primaryKey = 'prv_id';

    protected $fillable = [
        'prv_clave',
        'prv_nombre_empresa',
        'prv_nombre_asesor_ventas',
        'prv_categoria',
        'prv_razon_social',
        'prv_rfc',
        'prv_correo',
        'prv_condiciones_pago',
        'prv_tiempo_respuesta',
        'prv_estatus',
        'prv_created_by_usr_id',
        'prv_updated_by_usr_id',
    ];

    public function contactos()
    {
        return $this->hasMany(ProveedorContacto::class, 'prc_prv_id', 'prv_id')
            ->orderBy('prc_orden')
            ->orderBy('prc_id');
    }
}
