<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Model;

class EtiquetaLineaConfiguracion extends Model
{
    use HasLogicalDeletion;
    public const CREATED_AT = 'elc_created_at'; public const UPDATED_AT = 'elc_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'elc_deleted'; public const LOGICAL_DELETED_AT_COLUMN = 'elc_deleted_at';
    protected $table = 'tbl_etiqueta_linea_config_elc'; protected $primaryKey = 'elc_id';
    protected $fillable = ['elc_lna_id','elc_etf_id','elc_etp_id','elc_estatus','elc_created_by_usr_id','elc_updated_by_usr_id'];
    public function linea() { return $this->belongsTo(Linea::class, 'elc_lna_id', 'lna_id'); }
    public function formato() { return $this->belongsTo(EtiquetaFormato::class, 'elc_etf_id', 'etf_id'); }
    public function plantilla() { return $this->belongsTo(EtiquetaPlantilla::class, 'elc_etp_id', 'etp_id'); }
}
