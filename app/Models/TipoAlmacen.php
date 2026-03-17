<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoAlmacen extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'tal_created_at';
    public const UPDATED_AT = 'tal_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'tal_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'tal_deleted_at';

    protected $table = 'tbl_tipos_almacen_tal';
    protected $primaryKey = 'tal_id';

    protected $fillable = [
        'tal_nombre',
        'tal_clave',
        'tal_descripcion',
        'tal_estatus',
        'tal_created_by_usr_id',
        'tal_updated_by_usr_id',
    ];

    public function almacenes()
    {
        return $this->hasMany(Almacen::class, 'alm_tal_id', 'tal_id');
    }
}
