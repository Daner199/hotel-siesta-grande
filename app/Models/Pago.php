<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Un pago de una reserva, en bolivianos. Para registrar usar App\Support\Pagos.
 * APROBADO suma a lo pagado; RECHAZADO queda como historial; DEVUELTO = se devolvió al cancelar.
 */
class Pago extends Model
{
    public const PENDIENTE = 'PENDIENTE';
    public const APROBADO  = 'APROBADO';
    public const RECHAZADO = 'RECHAZADO';
    public const DEVUELTO  = 'DEVUELTO';

    protected $table = 'pago';

    // Solo tiene fecha_pago (la pone la BD)
    public $timestamps = false;

    protected $fillable = [
        'reserva_id', 'metodo_pago_id', 'monto', 'estado', 'referencia', 'observacion', 'registrado_por_usuario_id',
    ];

    protected $casts = [
        'monto'      => 'decimal:2',
        'fecha_pago' => 'datetime',
    ];

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }

    public function metodo(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class, 'metodo_pago_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'registrado_por_usuario_id');
    }

    // Solo los pagos por transferencia
    public function transferencia(): HasOne
    {
        return $this->hasOne(Transferencia::class, 'pago_id');
    }

    public function recibo(): HasOne
    {
        return $this->hasOne(Recibo::class, 'pago_id');
    }

    public function etiquetaEstado(): string
    {
        return match ($this->estado) {
            self::APROBADO  => 'Aprobado',
            self::RECHAZADO => 'Rechazado',
            self::DEVUELTO  => 'Devuelto',
            default         => 'En revisión',
        };
    }
}
