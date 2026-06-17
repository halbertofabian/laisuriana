<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModeloProducto extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const LOGICAL_DELETION_UNIQUE_COLUMNS = [
        'mdl_nombre' => 120,
        'mdl_clave' => 40,
    ];

    public const CREATED_AT = 'mdl_created_at';
    public const UPDATED_AT = 'mdl_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'mdl_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'mdl_deleted_at';

    protected $table = 'tbl_modelos_mdl';
    protected $primaryKey = 'mdl_id';

    protected $fillable = [
        'mdl_nombre',
        'mdl_clave',
        'mdl_estatus',
        'mdl_created_by_usr_id',
        'mdl_updated_by_usr_id',
    ];

    protected function mdlNombre(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => is_string($value) ? mb_strtoupper($value) : $value,
            set: fn ($value) => is_string($value) ? mb_strtoupper(trim($value)) : $value,
        );
    }

    protected function mdlClave(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => is_string($value) ? mb_strtoupper($value) : $value,
            set: fn ($value) => is_string($value) ? mb_strtoupper(trim($value)) : $value,
        );
    }

    public function marcas()
    {
        return $this->belongsToMany(
            Marca::class,
            'tbl_modelo_marcas_mdm',
            'mdm_mdl_id',
            'mdm_mrc_id'
        )->withTimestamps('mdm_created_at', 'mdm_created_at');
    }

    public function productos()
    {
        return $this->hasMany(Producto::class, 'prd_mdl_id', 'mdl_id');
    }
}
