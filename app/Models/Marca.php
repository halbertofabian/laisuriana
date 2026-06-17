<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Marca extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'mrc_created_at';
    public const UPDATED_AT = 'mrc_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'mrc_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'mrc_deleted_at';
    public const LOGICAL_DELETION_UNIQUE_COLUMNS = [
        'mrc_nombre' => 120,
        'mrc_clave' => 40,
    ];

    protected $table = 'tbl_marcas_mrc';
    protected $primaryKey = 'mrc_id';

    protected $fillable = [
        'mrc_nombre',
        'mrc_clave',
        'mrc_estatus',
        'mrc_created_by_usr_id',
        'mrc_updated_by_usr_id',
    ];

    protected function mrcNombre(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => is_string($value) ? mb_strtoupper($value) : $value,
            set: fn ($value) => is_string($value) ? mb_strtoupper(trim($value)) : $value,
        );
    }

    public function modelos()
    {
        return $this->belongsToMany(
            ModeloProducto::class,
            'tbl_modelo_marcas_mdm',
            'mdm_mrc_id',
            'mdm_mdl_id'
        )->withTimestamps('mdm_created_at', 'mdm_created_at');
    }
}
