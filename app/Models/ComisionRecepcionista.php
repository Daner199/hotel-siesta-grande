<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Comisión de Bs 5 para el recepcionista que confirma una reserva.
 * Una sola por reserva (UNIQUE reserva_id en la BD).
 */
class ComisionRecepcionista extends Model
{
    public const MONTO = 5;

    protected $table = 'comision_recepcionista';

    public $timestamps = false;

    protected $fillable = ['reserva_id', 'recepcionista_id', 'monto', 'pagada'];

    protected $casts = [
        'monto'            => 'decimal:2',
        'fecha_generacion' => 'datetime',
        'pagada'           => 'boolean',
    ];

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }

    public function recepcionista(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'recepcionista_id');
    }
}
