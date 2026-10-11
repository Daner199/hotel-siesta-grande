<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un cobro con QR dinámico: monto fijo, vence a los 15 minutos.
 * Lo paga Banco Siesta (simulado) y avisa por webhook. Ver App\Support\CobrosQr.
 */
class CobroQr extends Model
{
    public const PENDIENTE = 'PENDIENTE';
    public const PAGADO    = 'PAGADO';
    public const RECHAZADO = 'RECHAZADO';
    public const VENCIDO   = 'VENCIDO';
    public const ANULADO   = 'ANULADO';

    protected $table = 'cobro_qr';

    const UPDATED_AT = null;

    protected $fillable = ['token', 'reserva_id', 'monto', 'vence_en', 'estado', 'pago_id', 'creado_por_usuario_id'];

    protected $casts = [
        'monto'    => 'decimal:2',
        'vence_en' => 'datetime',
    ];

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class, 'pago_id');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'creado_por_usuario_id');
    }

    // Pendiente pero ya pasó la hora (se marca VENCIDO al consultarlo)
    public function vencido(): bool
    {
        return $this->estado === self::PENDIENTE && $this->vence_en->isPast();
    }
}
