<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ValorAtributo extends Model
{
    use HasFactory;
    use HasLogicalDeletion;

    public const CREATED_AT = 'vat_created_at';
    public const UPDATED_AT = 'vat_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'vat_deleted';
    public const LOGICAL_DELETED_AT_COLUMN = 'vat_deleted_at';

    protected $table = 'tbl_valores_atributo_vat';
    protected $primaryKey = 'vat_id';

    protected $fillable = [
        'vat_atr_id',
        'vat_valor',
        'vat_clave',
        'vat_estatus',
        'vat_created_by_usr_id',
        'vat_updated_by_usr_id',
    ];

    public function atributo()
    {
        return $this->belongsTo(Atributo::class, 'vat_atr_id', 'atr_id');
    }
}
