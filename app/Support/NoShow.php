<?php

namespace App\Support;

use App\Models\CobroQr;
use App\Models\EstadoReserva;
use App\Models\Pago;
use App\Models\Reserva;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Política 5 (no-show): si el huésped no hizo check-in hasta las 12:00 del día siguiente a su llegada
 * (hora de La Paz), la reserva CONFIRMADA pasa a NO_SHOW y la habitación queda libre.
 * El adelanto no se devuelve y la comisión se conserva.
 *
 * Además, las PENDIENTE con el mismo plazo vencido (nunca pagaron el adelanto) pasan a CANCELADA
 * con la nota "Vencida sin pago". Las que tienen una transferencia en revisión no se tocan.
 *
 * Se ejecuta con "php artisan reservas:no-show", sola cada 5 minutos cuando alguien abre recepción
 * o busca disponibilidad (NoShow::siCorresponde) y con el botón "Procesar no-show".
 */
class NoShow
{
    public const NOTA_VENCIDA = 'Vencida sin pago.';

    /**
     * @return array{no_show: int, liberadas: int}
     */
    public static function procesar(): array
    {
        // Última fecha de llegada con el plazo vencido: antes de las 12:00 → anteayer; desde las 12:00 → ayer
        $ultimaLlegada = now()->hour >= Estadias::HORA_NO_SHOW ? today()->subDay() : today()->subDays(2);

        return DB::transaction(function () use ($ultimaLlegada) {
            $vencidas = fn (string $estado) => Reserva::whereHas('estado', fn ($q) => $q->where('nombre', $estado))
                ->where('fecha_entrada', '<=', $ultimaLlegada->toDateString())
                ->lockForUpdate();

            // Confirmadas que no llegaron → NO_SHOW (la habitación queda libre)
            $noShow = $vencidas(EstadoReserva::CONFIRMADA)->pluck('id');
            Reserva::whereKey($noShow)->update(['estado_reserva_id' => EstadoReserva::id(EstadoReserva::NO_SHOW)]);
            CobroQr::whereIn('reserva_id', $noShow)->where('estado', CobroQr::PENDIENTE)->update(['estado' => CobroQr::ANULADO]);
            $noShow = $noShow->count();

            // Pendientes que nunca pagaron el adelanto → CANCELADA (salvo transferencia en revisión)
            $pendientes = $vencidas(EstadoReserva::PENDIENTE)
                ->whereDoesntHave('pagos', fn ($q) => $q->where('estado', Pago::PENDIENTE)->whereHas('transferencia'))
                ->get(['id', 'observacion']);

            foreach ($pendientes as $r) {
                $r->update([
                    'estado_reserva_id' => EstadoReserva::id(EstadoReserva::CANCELADA),
                    'observacion'       => trim(($r->observacion ? $r->observacion . ' ' : '') . self::NOTA_VENCIDA),
                ]);
                CobroQr::where('reserva_id', $r->id)->where('estado', CobroQr::PENDIENTE)
                    ->update(['estado' => CobroQr::ANULADO]);
            }

            return ['no_show' => $noShow, 'liberadas' => $pendientes->count()];
        });
    }

    // Como máximo una vez cada 5 minutos (sin dejar otra terminal abierta)
    public static function siCorresponde(): void
    {
        if (Cache::add('reservas.no_show', true, now()->addMinutes(5))) {
            self::procesar();
        }
    }
}
