<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosTicketConfiguracion extends Model
{
    use HasFactory;

    public const CREATED_AT = 'ptc_created_at';
    public const UPDATED_AT = 'ptc_updated_at';

    protected $table = 'tbl_pos_ticket_configuracion_ptc';
    protected $primaryKey = 'ptc_id';

    protected $fillable = [
        'ptc_logo_path',
        'ptc_texto_encabezado',
        'ptc_texto_pie',
        'ptc_created_by_usr_id',
        'ptc_updated_by_usr_id',
    ];

    public static function singleton(): self
    {
        return static::query()->firstOrCreate([], []);
    }
}
