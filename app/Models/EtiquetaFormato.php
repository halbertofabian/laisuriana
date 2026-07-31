<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDeletion;
use Illuminate\Database\Eloquent\Model;

class EtiquetaFormato extends Model
{
    use HasLogicalDeletion;
    public const CREATED_AT = 'etf_created_at'; public const UPDATED_AT = 'etf_updated_at';
    public const LOGICAL_DELETED_COLUMN = 'etf_deleted'; public const LOGICAL_DELETED_AT_COLUMN = 'etf_deleted_at';
    protected $table = 'tbl_etiqueta_formatos_etf'; protected $primaryKey = 'etf_id';
    protected $fillable = ['etf_nombre','etf_descripcion','etf_ancho_mm','etf_alto_mm','etf_orientacion','etf_margen_izq_mm','etf_margen_der_mm','etf_margen_sup_mm','etf_margen_inf_mm','etf_tipo_salida','etf_columnas','etf_filas','etf_separacion_h_mm','etf_separacion_v_mm','etf_compatibilidad_impresora','etf_estatus','etf_created_by_usr_id','etf_updated_by_usr_id'];
    protected function casts(): array { return ['etf_ancho_mm'=>'decimal:2','etf_alto_mm'=>'decimal:2','etf_margen_izq_mm'=>'decimal:2','etf_margen_der_mm'=>'decimal:2','etf_margen_sup_mm'=>'decimal:2','etf_margen_inf_mm'=>'decimal:2','etf_separacion_h_mm'=>'decimal:2','etf_separacion_v_mm'=>'decimal:2']; }
    public function configuracionesLinea() { return $this->hasMany(EtiquetaLineaConfiguracion::class, 'elc_etf_id', 'etf_id'); }
}
