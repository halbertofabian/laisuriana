<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Concepto extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'cpt_created_at';
    public const UPDATED_AT = 'cpt_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'cpt_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'cpt_deleted_at';

    protected $table = 'tbl_conceptos_cpt';
    protected $primaryKey = 'cpt_id';

    protected $fillable = [
        'cpt_nombre',
        'cpt_clave',
        'cpt_estatus',
        'cpt_created_by_usr_id',
        'cpt_updated_by_usr_id',
    ];
}
