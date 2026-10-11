<?php

namespace App\Models;

use App\Support\Disponibilidad;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

/**
 * Reserva de una o varias habitaciones entre [fecha_entrada, fecha_salida).
 * Es de un cliente, de una agencia o de ninguno (la creó recepción para alguien sin cuenta).
 *
 * El total NO se guarda: se calcula con el precio guardado en cada habitación (3FN).
 * Para crear, cancelar o confirmar usar App\Support\Reservas.
 */
class Reserva extends Model
{
    public const HORAS_CANCELACION = 48;

    protected $table = 'reserva';

    // Solo tiene fecha_creacion (la pone la BD)
    public $timestamps = false;

    protected $fillable = [
        'codigo', 'cliente_id', 'agencia_id', 'creado_por_usuario_id', 'estado_reserva_id',
        'nombre_titular', 'cantidad_personas', 'fecha_entrada', 'fecha_salida', 'observacion',
    ];

    protected $casts = [
        'cantidad_personas' => 'integer',
        'fecha_entrada'     => 'date',
        'fecha_salida'      => 'date',
        'fecha_creacion'    => 'datetime',
    ];

    public function estado(): BelongsTo
    {
        return $this->belongsTo(EstadoReserva::class, 'estado_reserva_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'cliente_id');
    }

    public function agencia(): BelongsTo
    {
        return $this->belongsTo(Agencia::class, 'agencia_id');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'creado_por_usuario_id');
    }

    // Habitaciones de la reserva con su precio aplicado
    public function habitaciones(): HasMany
    {
        return $this->hasMany(ReservaHabitacion::class, 'reserva_id')->orderBy('id');
    }

    public function comision(): HasOne
    {
        return $this->hasOne(ComisionRecepcionista::class, 'reserva_id');
    }

    public function esEstado(string $nombre): bool
    {
        return $this->estado->nombre === $nombre;
    }

    public function noches(): int
    {
        return (int) $this->fecha_entrada->diffInDays($this->fecha_salida);
    }

    // Suma de todas las habitaciones (cada una con el 15 % desde la 8.ª noche)
    public function total(): float
    {
        $noches = $this->noches();

        return round($this->habitaciones->sum(
            fn ($h) => Disponibilidad::calcularEstadia((float) $h->precio_noche_aplicado, $noches)['total']
        ), 2);
    }

    // Política 1: adelanto mínimo del 30 %
    public function adelanto(): float
    {
        return Disponibilidad::adelanto($this->total());
    }

    // Pagos aprobados (los registra el Módulo 5)
    public function totalPagado(): float
    {
        return round((float) DB::table('pago')
            ->where('reserva_id', $this->id)
            ->where('estado', 'APROBADO')
            ->sum('monto'), 2);
    }

    // Política 4: hasta este momento se puede cancelar con devolución total
    // (fecha de entrada a la hora de check-in, menos 48 h, hora de Bolivia)
    public function limiteCancelacion(): Carbon
    {
        $checkIn = Hotel::datos()['check_in'] ?? '14:00';

        return Carbon::parse($this->fecha_entrada->toDateString() . ' ' . $checkIn, config('app.timezone'))
            ->subHours(self::HORAS_CANCELACION);
    }

    // ¿Se devuelve el adelanto si se cancela ahora? (justo en el límite, sí)
    public function devuelveSiCancela(?Carbon $momento = null): bool
    {
        return ($momento ?? now())->lte($this->limiteCancelacion());
    }

    public function sePuedeCancelar(): bool
    {
        return in_array($this->estado->nombre, [EstadoReserva::PENDIENTE, EstadoReserva::CONFIRMADA], true);
    }
}
