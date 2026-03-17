<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnidadMedida extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'umd_created_at';
    public const UPDATED_AT = 'umd_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'umd_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'umd_deleted_at';

    protected $table = 'tbl_unidades_medida_umd';
    protected $primaryKey = 'umd_id';

    protected $fillable = [
        'umd_nombre',
        'umd_codigo',
        'umd_tipo_cantidad',
        'umd_es_predeterminada',
        'umd_clave',
        'umd_estatus',
        'umd_created_by_usr_id',
        'umd_updated_by_usr_id',
    ];

    protected function casts(): array
    {
        return [
            'umd_es_predeterminada' => 'boolean',
        ];
    }
}
