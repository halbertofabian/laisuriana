<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'cli_created_at';
    public const UPDATED_AT = 'cli_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'cli_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'cli_deleted_at';

    protected $table = 'tbl_clientes_cli';
    protected $primaryKey = 'cli_id';

    protected $fillable = [
        'cli_nombre',
        'cli_apellido_paterno',
        'cli_apellido_materno',
        'cli_razon_social',
        'cli_fecha_nacimiento',
        'cli_telefono',
        'cli_whatsapp',
        'cli_email',
        'cli_rfc',
        'cli_curp',
        'cli_ine',
        'cli_cp',
        'cli_colonia',
        'cli_tipo_asentamiento',
        'cli_municipio',
        'cli_estado',
        'cli_ciudad',
        'cli_calle',
        'cli_num_ext',
        'cli_num_int',
        'cli_referencias',
        'cli_estatus',
        'cli_created_by_usr_id',
        'cli_updated_by_usr_id',
    ];

    protected function casts(): array
    {
        return [
            'cli_fecha_nacimiento' => 'date',
            'cli_deleted' => 'boolean',
            'cli_created_at' => 'datetime',
            'cli_updated_at' => 'datetime',
            'cli_deleted_at' => 'datetime',
        ];
    }
}

