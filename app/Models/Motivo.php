<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Motivo extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const LOGICAL_DELETION_UNIQUE_COLUMNS = [
        'mtv_nombre' => 120,
        'mtv_clave' => 40,
    ];

    public const CREATED_AT = 'mtv_created_at';
    public const UPDATED_AT = 'mtv_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'mtv_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'mtv_deleted_at';

    protected $table = 'tbl_motivos_mtv';
    protected $primaryKey = 'mtv_id';

    protected $fillable = [
        'mtv_nombre',
        'mtv_clave',
        'mtv_estatus',
        'mtv_created_by_usr_id',
        'mtv_updated_by_usr_id',
    ];

    protected function mtvNombre(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => is_string($value) ? mb_strtoupper($value) : $value,
            set: fn ($value) => is_string($value) ? mb_strtoupper(trim($value)) : $value,
        );
    }

    protected function mtvClave(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => is_string($value) ? mb_strtoupper($value) : $value,
            set: fn ($value) => is_string($value) ? mb_strtoupper(trim($value)) : $value,
        );
    }
}
