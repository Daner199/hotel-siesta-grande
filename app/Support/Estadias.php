<?php

namespace App\Support;

use App\Models\EstadoHabitacion;
use App\Models\EstadoReserva;
use App\Models\Habitacion;
use App\Models\Reserva;
use App\Models\ReservaHabitacion;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Estadía del huésped (recepción): check-in, cambio de habitación y check-out.
 *
 * - Check-in: reserva CONFIRMADA, desde el día de llegada hasta el plazo del no-show
 *   (12:00 del día siguiente, hora de La Paz). Todas sus habitaciones deben estar ACTIVAS.
 * - Check-out: reserva en CHECK_IN con saldo 0. Si sale antes, se cobra igual toda la estadía
 *   y la habitación queda libre desde ese momento.
 */
class Estadias
{
    public const HORA_NO_SHOW = 12; // 12:00 del día siguiente a la llegada

    // Hasta cuándo puede llegar el huésped (después pasa a NO_SHOW)
    public static function plazoNoShow(Reserva $reserva): Carbon
    {
        return Carbon::parse($reserva->fecha_entrada->toDateString(), config('app.timezone'))
            ->addDay()->setTime(self::HORA_NO_SHOW, 0);
    }

    // Motivo por el que no se puede hacer el check-in ahora (null = se puede)
    public static function motivoSinCheckIn(Reserva $reserva): ?string
    {
        return match (true) {
            $reserva->esEstado(EstadoReserva::PENDIENTE)  => 'Falta pagar el adelanto.',
            $reserva->esEstado(EstadoReserva::CHECK_IN)   => 'Ya hizo el check-in.',
            ! $reserva->esEstado(EstadoReserva::CONFIRMADA) => 'La reserva está ' . mb_strtolower($reserva->estado->etiqueta()) . '.',
            today()->lt($reserva->fecha_entrada)            => 'La llegada es el ' . $reserva->fecha_entrada->format('d/m/Y') . '.',
            now()->gte(self::plazoNoShow($reserva))         => 'Venció el plazo de llegada (no-show).',
            default                                          => null,
        };
    }

    public static function checkIn(Reserva $reserva, Usuario $recepcionista): Reserva
    {
        return DB::transaction(function () use ($reserva, $recepcionista) {
            $reserva = Reserva::whereKey($reserva->id)->lockForUpdate()
                ->with(['estado', 'habitaciones.habitacion.estado'])->firstOrFail();

            if ($motivo = self::motivoSinCheckIn($reserva)) {
                throw ValidationException::withMessages(['reserva' => $motivo]);
            }

            $noActivas = $reserva->habitaciones->filter(fn ($rh) => $rh->habitacion->estado->nombre !== EstadoHabitacion::ACTIVA);
            if ($noActivas->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'reserva' => 'La habitación ' . $noActivas->map(fn ($rh) => $rh->habitacion->numero)->join(', ')
                        . ' no está disponible: cámbiala antes del check-in.',
                ]);
            }

            $reserva->update([
                'estado_reserva_id'       => EstadoReserva::id(EstadoReserva::CHECK_IN),
                'check_in_en'             => now(),
                'check_in_por_usuario_id' => $recepcionista->id,
            ]);

            return $reserva;
        });
    }

    public static function checkOut(Reserva $reserva, Usuario $recepcionista): Reserva
    {
        return DB::transaction(function () use ($reserva, $recepcionista) {
            $reserva = Reserva::whereKey($reserva->id)->lockForUpdate()->with(['estado', 'habitaciones'])->firstOrFail();

            if (! $reserva->esEstado(EstadoReserva::CHECK_IN)) {
                throw ValidationException::withMessages(['reserva' => 'El huésped no está alojado.']);
            }
            if (($saldo = $reserva->saldo()) > 0) {
                throw ValidationException::withMessages(['reserva' => 'Falta cobrar ' . Moneda::formato($saldo) . '.']);
            }

            $reserva->update([
                'estado_reserva_id'        => EstadoReserva::id(EstadoReserva::CHECK_OUT),
                'check_out_en'             => now(),
                'check_out_por_usuario_id' => $recepcionista->id,
            ]);

            return $reserva;
        });
    }

    // Habitaciones a las que se puede mover una habitación de la reserva: mismo tipo, activas y libres
    public static function alternativas(ReservaHabitacion $rh): \Illuminate\Support\Collection
    {
        $reserva = $rh->reserva;

        return Disponibilidad::habitacionesLibres($reserva->fecha_entrada, $reserva->fecha_salida)
            ->where('tipo_habitacion_id', $rh->habitacion->tipo_habitacion_id)
            ->orderByRaw('LENGTH(numero), numero')
            ->get(['id', 'numero', 'piso']);
    }

    /**
     * Cambia una habitación de la reserva por otra libre del mismo tipo (el precio no cambia).
     */
    public static function cambiarHabitacion(ReservaHabitacion $rh, Habitacion $nueva): ReservaHabitacion
    {
        $reserva = $rh->reserva()->with('estado')->first();

        if (! in_array($reserva->estado->nombre, EstadoReserva::OCUPAN, true)) {
            throw ValidationException::withMessages(['habitacion_id' => 'La reserva ya no está activa.']);
        }
        if ($nueva->tipo_habitacion_id !== $rh->habitacion->tipo_habitacion_id) {
            throw ValidationException::withMessages(['habitacion_id' => 'Elige una habitación del mismo tipo.']);
        }
        if (! self::alternativas($rh)->contains('id', $nueva->id)) {
            throw ValidationException::withMessages(['habitacion_id' => "La habitación {$nueva->numero} no está libre en esas fechas."]);
        }

        try {
            $rh->update(['habitacion_id' => $nueva->id]);
        } catch (QueryException $e) {
            if ($e->getCode() === '23P01') {
                throw ValidationException::withMessages(['habitacion_id' => Reservas::mensajeBd($e)]);
            }
            throw $e;
        }

        return $rh;
    }

    /**
     * Reservas activas (PENDIENTE, CONFIRMADA o CHECK_IN) que todavía no terminaron y usan esta habitación.
     * Mientras tenga alguna, el admin no puede sacarla de servicio ni cambiarle el tipo.
     */
    public static function reservasActivasDe(Habitacion $habitacion): \Illuminate\Support\Collection
    {
        return Reserva::whereHas('habitaciones', fn ($q) => $q->where('habitacion_id', $habitacion->id))
            ->whereHas('estado', fn ($q) => $q->whereIn('nombre', EstadoReserva::OCUPAN))
            ->where('fecha_salida', '>', today()->toDateString())
            ->orderBy('fecha_entrada')
            ->pluck('codigo');
    }

    // Listas de recepción: llegadas, alojados, salidas y no-show (pestañas de Check-in / Check-out e inicio)
    public static function lista(string $pestana)
    {
        $hoy = today()->toDateString();
        $estado = fn (array $nombres) => fn ($q) => $q->whereIn('nombre', $nombres);

        return match ($pestana) {
            // Confirmadas (y pendientes, para cobrar el adelanto) que llegan hoy o ayer dentro del plazo
            'llegadas' => Reserva::whereHas('estado', $estado([EstadoReserva::CONFIRMADA, EstadoReserva::PENDIENTE]))
                ->where('fecha_entrada', '<=', $hoy)
                ->where('fecha_entrada', '>=', today()->subDay()->toDateString())
                ->orderBy('fecha_entrada')->orderBy('codigo'),
            'alojados' => Reserva::whereHas('estado', $estado([EstadoReserva::CHECK_IN]))
                ->orderBy('fecha_salida')->orderBy('codigo'),
            'salidas' => Reserva::whereHas('estado', $estado([EstadoReserva::CHECK_IN]))
                ->where('fecha_salida', '<=', $hoy)
                ->orderBy('fecha_salida')->orderBy('codigo'),
            // Últimos 30 días: no-show y pendientes liberadas sin pago
            default => Reserva::where(fn ($q) => $q
                    ->whereHas('estado', $estado([EstadoReserva::NO_SHOW]))
                    ->orWhere(fn ($w) => $w->whereHas('estado', $estado([EstadoReserva::CANCELADA]))
                        ->where('observacion', 'LIKE', '%' . NoShow::NOTA_VENCIDA . '%')))
                ->where('fecha_entrada', '>=', today()->subDays(30)->toDateString())
                ->orderByDesc('fecha_entrada')->orderBy('codigo'),
        };
    }
}
