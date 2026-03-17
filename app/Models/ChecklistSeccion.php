<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChecklistSeccion extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'chs_created_at';
    public const UPDATED_AT = 'chs_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'chs_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'chs_deleted_at';

    protected $table = 'tbl_checklist_secciones_chs';
    protected $primaryKey = 'chs_id';

    protected $fillable = [
        'chs_chk_id',
        'chs_titulo',
        'chs_descripcion',
        'chs_observacion',
        'chs_orden',
        'chs_estatus',
        'chs_created_by_usr_id',
        'chs_updated_by_usr_id',
    ];

    protected function casts(): array
    {
        return [
            'chs_orden' => 'integer',
        ];
    }

    public function checklist()
    {
        return $this->belongsTo(Checklist::class, 'chs_chk_id', 'chk_id');
    }

    public function items()
    {
        return $this->hasMany(ChecklistItem::class, 'chi_chs_id', 'chs_id');
    }
}
