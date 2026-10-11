<?php

namespace App\Support;

use App\Models\EstadoHabitacion;
use App\Models\EstadoReserva;
use App\Models\Habitacion;
use App\Models\TipoHabitacion;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Disponibilidad y cálculo de la estadía. La usan la landing y el Módulo 4 (reservas).
 *
 * Libres = habitaciones ACTIVAS de tipos activos que en [llegada, salida) no tengan
 * una reserva PENDIENTE, CONFIRMADA o CHECK_IN ni un bloqueo (si una sale el 15, otra entra el 15).
 * La BD revisa lo mismo con un trigger (script 07); esto es para mostrar mensajes claros.
 *
 * Precio por noche: tarifa del día de llegada para toda la estadía; agencia −10 %.
 * Política 3: más de 7 noches → desde la 8.ª noche, 15 % de descuento (sobre ese precio).
 * Política 1: adelanto mínimo del 30 % del total.
 */
class Disponibilidad
{
    public const ESTADOS_QUE_OCUPAN   = EstadoReserva::OCUPAN;
    public const NOCHES_PRECIO_NORMAL = 7;
    public const DESCUENTO_LARGA      = 15; // %
    public const DESCUENTO_AGENCIA    = 10; // %
    public const ADELANTO             = 30; // %
    public const MAX_NOCHES           = 90;

    // Habitaciones libres en [llegada, salida), para seguir filtrando (por tipo, por id...)
    public static function habitacionesLibres(Carbon $llegada, Carbon $salida): Builder
    {
        $desde = $llegada->toDateString();
        $hasta = $salida->toDateString();

        return Habitacion::query()
            ->whereHas('estado', fn ($q) => $q->where('nombre', EstadoHabitacion::ACTIVA))
            ->whereHas('tipo', fn ($q) => $q->where('activo', true))
            ->whereNotExists(function ($q) use ($desde, $hasta) {
                $q->select(DB::raw(1))
                  ->from('reserva_habitacion as rh')
                  ->join('reserva as r', 'r.id', '=', 'rh.reserva_id')
                  ->join('estado_reserva as er', 'er.id', '=', 'r.estado_reserva_id')
                  ->whereColumn('rh.habitacion_id', 'habitacion.id')
                  ->whereIn('er.nombre', self::ESTADOS_QUE_OCUPAN)
                  ->where('r.fecha_entrada', '<', $hasta)
                  ->where('r.fecha_salida', '>', $desde);
            })
            ->whereNotExists(function ($q) use ($desde, $hasta) {
                $q->select(DB::raw(1))
                  ->from('bloqueo_habitacion as b')
                  ->whereColumn('b.habitacion_id', 'habitacion.id')
                  ->where('b.fecha_desde', '<', $hasta)
                  ->where('b.fecha_hasta', '>', $desde);
            });
    }

    // [tipo_habitacion_id => cantidad de habitaciones libres]
    public static function libresPorTipo(Carbon $llegada, Carbon $salida): Collection
    {
        return self::habitacionesLibres($llegada, $salida)
            ->selectRaw('tipo_habitacion_id, COUNT(*) AS libres')
            ->groupBy('tipo_habitacion_id')
            ->pluck('libres', 'tipo_habitacion_id')
            ->map(fn ($n) => (int) $n);
    }

    /**
     * Precio por noche que se cobra: tarifa del día de llegada, −10 % si reserva una agencia.
     * null si el tipo no tiene tarifa ese día (no se puede reservar).
     */
    public static function precioNoche(TipoHabitacion $tipo, Carbon $llegada, bool $esAgencia = false): ?float
    {
        $tarifa = $tipo->tarifaVigente($llegada);

        if (! $tarifa) {
            return null;
        }

        $precio = (float) $tarifa->precio_noche;

        return $esAgencia ? round($precio * (100 - self::DESCUENTO_AGENCIA) / 100, 2) : $precio;
    }

    // "Estadía larga: 15 % menos desde la 8.ª noche" (lo usan la landing y las reservas)
    public static function etiquetaEstadiaLarga(): string
    {
        return 'Estadía larga: ' . self::DESCUENTO_LARGA . ' % menos desde la '
            . (self::NOCHES_PRECIO_NORMAL + 1) . '.ª noche';
    }

    // Política 1: 30 % del total
    public static function adelanto(float $total): float
    {
        return round($total * self::ADELANTO / 100, 2);
    }

    /**
     * Total de una habitación por toda la estadía, con el detalle del cálculo.
     *
     * El detalle es interno (para el Módulo 4 y reportes); la landing solo muestra
     * el total, el precio sin descuento y el promedio por noche.
     *
     * @return array{detalle: array<int, array{texto: string, monto: float}>, total: float, ahorro: float,
     *               sin_descuento: float, promedio: float, estadia_larga: bool}
     */
    public static function calcularEstadia(float $precioNoche, int $noches): array
    {
        $normales = min($noches, self::NOCHES_PRECIO_NORMAL);
        $conDesc  = max(0, $noches - self::NOCHES_PRECIO_NORMAL);
        $precioConDesc = round($precioNoche * (100 - self::DESCUENTO_LARGA) / 100, 2);

        $detalle = [[
            'texto' => $normales . ' ' . ($normales === 1 ? 'noche' : 'noches') . ' × ' . Moneda::formato($precioNoche),
            'monto' => round($normales * $precioNoche, 2),
        ]];

        if ($conDesc > 0) {
            $detalle[] = [
                'texto' => $conDesc . ' ' . ($conDesc === 1 ? 'noche' : 'noches') . ' desde la 8.ª × '
                    . Moneda::formato($precioConDesc) . ' (−' . self::DESCUENTO_LARGA . ' %)',
                'monto' => round($conDesc * $precioConDesc, 2),
            ];
        }

        $total = round(array_sum(array_column($detalle, 'monto')), 2);

        return [
            'detalle'       => $detalle,
            'total'         => $total,
            'ahorro'        => round($conDesc * ($precioNoche - $precioConDesc), 2),
            'sin_descuento' => round($noches * $precioNoche, 2),
            'promedio'      => $noches > 0 ? round($total / $noches, 2) : 0.0,
            'estadia_larga' => $conDesc > 0,
        ];
    }
}
