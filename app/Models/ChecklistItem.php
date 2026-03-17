<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChecklistItem extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'chi_created_at';
    public const UPDATED_AT = 'chi_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'chi_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'chi_deleted_at';

    protected $table = 'tbl_checklist_items_chi';
    protected $primaryKey = 'chi_id';

    protected $fillable = [
        'chi_chs_id',
        'chi_titulo',
        'chi_descripcion',
        'chi_referencia_funcional',
        'chi_estatus',
        'chi_observacion',
        'chi_orden',
        'chi_created_by_usr_id',
        'chi_updated_by_usr_id',
    ];

    protected function casts(): array
    {
        return [
            'chi_orden' => 'integer',
        ];
    }

    public function seccion()
    {
        return $this->belongsTo(ChecklistSeccion::class, 'chi_chs_id', 'chs_id');
    }
}
