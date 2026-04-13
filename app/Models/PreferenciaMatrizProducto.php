<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreferenciaMatrizProducto extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'pmp_created_at';
    public const UPDATED_AT = 'pmp_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'pmp_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'pmp_deleted_at';

    protected $table = 'tbl_preferencias_matriz_producto_pmp';
    protected $primaryKey = 'pmp_id';

    protected $fillable = [
        'pmp_prd_id',
        'pmp_scl_id',
        'pmp_atr_dominante_id',
        'pmp_estatus',
        'pmp_created_by_usr_id',
        'pmp_updated_by_usr_id',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'pmp_prd_id', 'prd_id');
    }

    public function atributoDominante()
    {
        return $this->belongsTo(Atributo::class, 'pmp_atr_dominante_id', 'atr_id');
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'pmp_scl_id', 'scl_id');
    }
}
