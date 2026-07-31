<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Model;

class EtiquetaUnidadRegla extends Model
{
    use HasLogicalDeletion;
    public const CREATED_AT = 'eur_created_at'; public const UPDATED_AT = 'eur_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'eur_deleted'; public const LOGICAL_DELETED_AT_COLUMN = 'eur_deleted_at';
    protected $table = 'tbl_etiqueta_unidad_reglas_eur'; protected $primaryKey = 'eur_id';
    protected $fillable = ['eur_umd_id','eur_regla','eur_estatus','eur_created_by_usr_id','eur_updated_by_usr_id'];
    public function unidad() { return $this->belongsTo(UnidadMedida::class, 'eur_umd_id', 'umd_id'); }
}
