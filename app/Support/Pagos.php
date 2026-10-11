<?php

namespace App\Support;

use App\Models\EstadoReserva;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Models\Reserva;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Reglas de los pagos (compartidas por cliente, agencia y recepción).
 *
 * - Reserva PENDIENTE: el pago debe cubrir de una vez lo que falta del adelanto (30 %).
 * - Después: cualquier monto hasta el saldo.
 * - QR y tarjeta pasan por la pasarela simulada; el efectivo solo lo registra recepción.
 * - Si con este pago se completa el adelanto, la reserva se confirma (Reservas::confirmar):
 *   si confirma un RECEPCIONISTA gana Bs 5 de comisión; en línea, no.
 */
class Pagos
{
    /**
     * Cuánto se puede pagar ahora: [mínimo, máximo] (en Bs). null si la reserva no admite pagos.
     *
     * @return array{minimo: float, maximo: float}|null
     */
    public static function limites(Reserva $reserva): ?array
    {
        if (! $reserva->sePuedePagar()) {
            return null;
        }

        $pagado = $reserva->totalPagado();
        $maximo = round($reserva->total() - $pagado, 2);
        $minimo = $reserva->esEstado(EstadoReserva::PENDIENTE)
            ? round(max(0.01, $reserva->adelanto() - $pagado), 2)
            : 0.01;

        return $maximo > 0 ? ['minimo' => min($minimo, $maximo), 'maximo' => $maximo] : null;
    }

    /**
     * Registra un pago. Devuelve el pago guardado: APROBADO o RECHAZADO (el rechazo también queda
     * guardado como historial). Los errores de regla se lanzan como ValidationException.
     *
     * Si el pago se aprueba, emite su recibo.
     *
     * @param string|null $ultimos4  solo los 4 últimos dígitos de la tarjeta (nunca el número ni el CVV)
     * @param array|null  $respuesta respuesta del banco ya recibida (webhook del QR): no se vuelve a cobrar
     */
    public static function registrar(Reserva $reserva, Usuario $quien, string $metodo, float $monto,
                                     ?string $ultimos4 = null, ?array $respuesta = null): Pago
    {
        $monto = round($monto, 2);

        if ($metodo === MetodoPago::EFECTIVO && $quien->rol !== Usuario::RECEPCIONISTA) {
            throw ValidationException::withMessages(['metodo' => 'El efectivo solo se paga en la recepción del hotel.']);
        }
        if ($metodo === MetodoPago::TARJETA && ! preg_match('/^\d{4}$/', (string) $ultimos4)) {
            throw ValidationException::withMessages(['tarjeta_numero' => 'Falta el número de la tarjeta.']);
        }

        return DB::transaction(function () use ($reserva, $quien, $metodo, $monto, $ultimos4, $respuesta) {
            // Bloquear la reserva: dos pagos a la vez no pueden pasarse del saldo
            $reserva = Reserva::whereKey($reserva->id)->lockForUpdate()->with('estado', 'habitaciones')->firstOrFail();

            $limites = self::limites($reserva);
            if (! $limites) {
                throw ValidationException::withMessages([
                    'monto' => $reserva->sePuedePagar()
                        ? 'Esta reserva ya está pagada por completo.'
                        : 'Una reserva ' . mb_strtolower($reserva->estado->etiqueta()) . ' no admite pagos.',
                ]);
            }
            if ($monto < $limites['minimo']) {
                throw ValidationException::withMessages([
                    'monto' => 'Para confirmar la reserva paga al menos el adelanto: ' . Moneda::formato($limites['minimo']) . '.',
                ]);
            }
            if ($monto > $limites['maximo']) {
                throw ValidationException::withMessages([
                    'monto' => 'El monto no puede pasar del saldo: ' . Moneda::formato($limites['maximo']) . '.',
                ]);
            }

            // Efectivo: aprobado al instante. QR/tarjeta: lo decide la pasarela simulada.
            $respuesta ??= $metodo === MetodoPago::EFECTIVO
                ? ['estado' => Pago::APROBADO, 'codigo' => PasarelaPagos::codigoNuevo('EFE')]
                : PasarelaPagos::cobrar($monto, $metodo, $ultimos4);

            $aprobado = $respuesta['estado'] === PasarelaPagos::APROBADO;

            $pago = Pago::create([
                'reserva_id'                => $reserva->id,
                'metodo_pago_id'            => MetodoPago::id($metodo),
                'monto'                     => $monto,
                'estado'                    => $aprobado ? Pago::APROBADO : Pago::RECHAZADO,
                'referencia'                => $aprobado ? $respuesta['codigo'] : null,
                'observacion'               => trim(implode(' ', array_filter([
                    $ultimos4 ? "Tarjeta terminada en {$ultimos4}." : null,
                    $aprobado ? null : ($respuesta['motivo'] ?? 'Pago rechazado.'),
                ]))) ?: null,
                'registrado_por_usuario_id' => $quien->id,
            ]);

            // ¿Con este pago se completó el adelanto? → confirmar (y comisión si es recepcionista)
            if ($aprobado && $reserva->esEstado(EstadoReserva::PENDIENTE)
                && $reserva->totalPagado() >= $reserva->adelanto()) {
                Reservas::confirmar($reserva, $quien);
            }

            if ($aprobado) {
                Recibos::emitir($pago);
            }

            return $pago;
        });
    }
}
