<?php
namespace App\Http\Requests\Operacion\Etiquetas;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StoreEtiquetaConfiguracionRequest extends FormRequest { public function authorize(): bool{return true;} public function rules():array{return ['elc_lna_id'=>['required','integer','exists:tbl_lineas_lna,lna_id'],'elc_etf_id'=>['required','integer','exists:tbl_etiqueta_formatos_etf,etf_id'],'elc_etp_id'=>['required','integer','exists:tbl_etiqueta_plantillas_etp,etp_id'],'elc_estatus'=>['required',Rule::in(['activo','inactivo'])]];} }
