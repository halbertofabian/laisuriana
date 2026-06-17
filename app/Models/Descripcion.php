<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Descripcion extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const LOGICAL_DELETION_UNIQUE_COLUMNS = [
        'dsc_nombre' => 120,
        'dsc_clave' => 40,
    ];

    public const CREATED_AT = 'dsc_created_at';
    public const UPDATED_AT = 'dsc_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'dsc_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'dsc_deleted_at';

    protected $table = 'tbl_descripciones_dsc';
    protected $primaryKey = 'dsc_id';

    protected $fillable = [
        'dsc_nombre',
        'dsc_clave',
        'dsc_estatus',
        'dsc_created_by_usr_id',
        'dsc_updated_by_usr_id',
    ];

    protected function dscNombre(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => is_string($value) ? mb_strtoupper($value) : $value,
            set: fn ($value) => is_string($value) ? mb_strtoupper(trim($value)) : $value,
        );
    }

    protected function dscClave(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => is_string($value) ? mb_strtoupper($value) : $value,
            set: fn ($value) => is_string($value) ? mb_strtoupper(trim($value)) : $value,
        );
    }
}
