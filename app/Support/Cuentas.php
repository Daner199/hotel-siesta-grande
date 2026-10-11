<?php

namespace App\Support;

use App\Models\Pago;
use App\Models\Reserva;

/**
 * Cuenta del huésped: estadía + consumos − pagos aprobados = saldo.
 * La usan recepción (check-out), el cliente y la agencia, y luego el reporte 5.
 */
class Cuentas
{
    /**
     * @return array{lineas: array, estadia: float, consumos: \Illuminate\Support\Collection, totalConsumos: float,
     *               porTipo: array, pagos: \Illuminate\Support\Collection, pagado: float, total: float, saldo: float}
     */
    public static function de(Reserva $reserva): array
    {
        $reserva->loadMissing(['habitaciones.habitacion.tipo', 'consumos.tipo', 'consumos.registradoPor', 'pagos.metodo', 'pagos.recibo']);

        $estadia  = $reserva->total();
        $consumos = $reserva->consumos;
        $totalConsumos = round($consumos->sum(fn ($c) => $c->importe()), 2);
        $pagos  = $reserva->pagos->where('estado', Pago::APROBADO)->values();
        $pagado = round($pagos->sum(fn ($p) => (float) $p->monto), 2);

        return [
            'lineas'        => Reservas::lineasEstadia($reserva),
            'estadia'       => $estadia,
            'consumos'      => $consumos,
            'totalConsumos' => $totalConsumos,
            // ['Restaurante' => 45.0, 'Frigobar' => 10.0]
            'porTipo'       => $consumos->groupBy(fn ($c) => $c->tipo->etiqueta())
                                        ->map(fn ($g) => round($g->sum(fn ($c) => $c->importe()), 2))->all(),
            'pagos'         => $pagos,
            'pagado'        => $pagado,
            'total'         => round($estadia + $totalConsumos, 2),
            'saldo'         => round(max(0, $estadia + $totalConsumos - $pagado), 2),
        ];
    }
}
