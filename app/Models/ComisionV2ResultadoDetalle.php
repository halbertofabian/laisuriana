<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComisionV2ResultadoDetalle extends Model
{
    public const CREATED_AT = 'crx_created_at';
    public const UPDATED_AT = 'crx_updated_at';

    protected $table = 'tbl_comision_v2_resultado_detalles_crx';
    protected $primaryKey = 'crx_id';
    protected $guarded = [];
}
