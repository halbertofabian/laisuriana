<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Checklist extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'chk_created_at';
    public const UPDATED_AT = 'chk_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'chk_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'chk_deleted_at';

    protected $table = 'tbl_checklists_chk';
    protected $primaryKey = 'chk_id';

    protected $fillable = [
        'chk_nombre',
        'chk_referencia',
        'chk_fecha',
        'chk_estatus_general',
        'chk_es_plantilla',
        'chk_observaciones',
        'chk_created_by_usr_id',
        'chk_updated_by_usr_id',
    ];

    protected function casts(): array
    {
        return [
            'chk_fecha' => 'date',
            'chk_es_plantilla' => 'boolean',
        ];
    }

    public function secciones()
    {
        return $this->hasMany(ChecklistSeccion::class, 'chs_chk_id', 'chk_id');
    }

    public function items()
    {
        return $this->hasManyThrough(
            ChecklistItem::class,
            ChecklistSeccion::class,
            'chs_chk_id',
            'chi_chs_id',
            'chk_id',
            'chs_id'
        );
    }
}
