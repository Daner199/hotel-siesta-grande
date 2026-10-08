<?php

namespace App\Support;

use App\Models\EstadoHabitacion;
use App\Models\Habitacion;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Disponibilidad y cálculo de la estadía. La usan la landing y (después) el Módulo 4.
 *
 * Libres = habitaciones ACTIVAS de tipos activos que no tengan una reserva PENDIENTE,
 * CONFIRMADA o CHECK_IN que se solape con [llegada, salida) (si una sale el 15, otra entra el 15).
 *
 * Política 3: más de 7 noches → desde la 8.ª noche, 15 % de descuento.
 * Tarifa: la vigente el día de llegada para toda la estadía (decisión provisional, ver Módulo 4).
 */
class Disponibilidad
{
    public const ESTADOS_QUE_OCUPAN   = ['PENDIENTE', 'CONFIRMADA', 'CHECK_IN'];
    public const NOCHES_PRECIO_NORMAL = 7;
    public const DESCUENTO_LARGA      = 15; // %

    // [tipo_habitacion_id => cantidad de habitaciones libres]
    public static function libresPorTipo(Carbon $llegada, Carbon $salida): Collection
    {
        return Habitacion::query()
            ->whereHas('estado', fn ($q) => $q->where('nombre', EstadoHabitacion::ACTIVA))
            ->whereHas('tipo', fn ($q) => $q->where('activo', true))
            ->whereNotExists(function ($q) use ($llegada, $salida) {
                $q->select(DB::raw(1))
                  ->from('reserva_habitacion as rh')
                  ->join('reserva as r', 'r.id', '=', 'rh.reserva_id')
                  ->join('estado_reserva as er', 'er.id', '=', 'r.estado_reserva_id')
                  ->whereColumn('rh.habitacion_id', 'habitacion.id')
                  ->whereIn('er.nombre', self::ESTADOS_QUE_OCUPAN)
                  ->where('r.fecha_entrada', '<', $salida->toDateString())
                  ->where('r.fecha_salida', '>', $llegada->toDateString());
            })
            ->selectRaw('tipo_habitacion_id, COUNT(*) AS libres')
            ->groupBy('tipo_habitacion_id')
            ->pluck('libres', 'tipo_habitacion_id')
            ->map(fn ($n) => (int) $n);
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
