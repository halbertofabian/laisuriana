<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Linea extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const LOGICAL_DELETION_UNIQUE_COLUMNS = [
        'lna_nombre' => 120,
        'lna_clave' => 40,
    ];

    public const CREATED_AT = 'lna_created_at';

    public const UPDATED_AT = 'lna_updated_at';

    public const LOGICAL_DELETED_COLUMN = 'lna_deleted';

    public const LOGICAL_DELETED_AT_COLUMN = 'lna_deleted_at';

    protected $table = 'tbl_lineas_lna';

    protected $primaryKey = 'lna_id';

    protected $fillable = [
        'lna_nombre',
        'lna_clave',
        'lna_estatus',
        'lna_created_by_usr_id',
        'lna_updated_by_usr_id',
    ];

    protected function lnaNombre(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => is_string($value) ? mb_strtoupper($value) : $value,
            set: fn ($value) => is_string($value) ? mb_strtoupper(trim($value)) : $value,
        );
    }

    protected function lnaClave(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => is_string($value) ? mb_strtoupper($value) : $value,
            set: fn ($value) => is_string($value) ? mb_strtoupper(trim($value)) : $value,
        );
    }

    public function grupoComision()
    {
        return $this->belongsToMany(ComisionGrupo::class, 'tbl_comision_grupo_lineas_cgl', 'cgl_lna_id', 'cgl_cgr_id');
    }
}
