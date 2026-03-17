<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Atributo extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'atr_created_at';
    public const UPDATED_AT = 'atr_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'atr_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'atr_deleted_at';

    protected $table = 'tbl_atributos_atr';
    protected $primaryKey = 'atr_id';

    protected $fillable = [
        'atr_nombre',
        'atr_clave',
        'atr_tipo',
        'atr_estatus',
        'atr_created_by_usr_id',
        'atr_updated_by_usr_id',
    ];

    public function valores()
    {
        return $this->hasMany(ValorAtributo::class, 'vat_atr_id', 'atr_id');
    }
}
