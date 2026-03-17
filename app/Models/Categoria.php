<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'ctg_created_at';
    public const UPDATED_AT = 'ctg_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'ctg_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'ctg_deleted_at';

    protected $table = 'tbl_categorias_ctg';
    protected $primaryKey = 'ctg_id';

    protected $fillable = [
        'ctg_nombre',
        'ctg_lna_id',
        'ctg_clave',
        'ctg_estatus',
        'ctg_created_by_usr_id',
        'ctg_updated_by_usr_id',
    ];

    public function linea()
    {
        return $this->belongsTo(Linea::class, 'ctg_lna_id', 'lna_id');
    }
}
