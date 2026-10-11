<?php

namespace App\Support;

use App\Models\EstadoReserva;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Models\Reserva;
use App\Models\Transferencia;
use App\Models\Usuario;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Reglas de los pagos (compartidas por cliente, agencia y recepción).
 *
 * - Reserva PENDIENTE: el pago debe cubrir de una vez lo que falta del adelanto (30 %).
 * - Después: cualquier monto hasta el saldo.
 * - Tarjeta: pasarela simulada. QR: Banco Siesta por webhook (CobrosQr). Efectivo: solo recepción.
 * - Transferencia: queda "en revisión" (PENDIENTE) hasta que recepción la aprueba o la rechaza.
 *   Mientras hay una en revisión no se aceptan otros pagos ni se puede cancelar la reserva.
 * - Si con un pago se completa el adelanto, la reserva se confirma (Reservas::confirmar):
 *   si confirma un RECEPCIONISTA gana Bs 5 de comisión; en línea, no.
 * - Todo pago aprobado tiene su recibo (Recibos::emitir).
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
        if (! $reserva->sePuedePagar() || $reserva->transferenciaEnRevision()) {
            return null;
        }

        $pagado = $reserva->totalPagado();
        $maximo = $reserva->saldo();
        $minimo = $reserva->esEstado(EstadoReserva::PENDIENTE)
            ? round(max(0.01, $reserva->adelanto() - $pagado), 2)
            : 0.01;

        return $maximo > 0 ? ['minimo' => min($minimo, $maximo), 'maximo' => $maximo] : null;
    }

    // Lanza ValidationException si el monto no se puede pagar ahora (lo usan el pago y el cobro con QR)
    public static function validarMonto(Reserva $reserva, float $monto): void
    {
        $limites = self::limites($reserva);
        if (! $limites) {
            throw ValidationException::withMessages([
                'monto' => self::motivoSinPago($reserva),
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
    }

    // Por qué una reserva no admite pagos ahora
    public static function motivoSinPago(Reserva $reserva): string
    {
        return match (true) {
            ! $reserva->sePuedePagar()                => 'Una reserva ' . mb_strtolower($reserva->estado->etiqueta()) . ' no admite pagos.',
            (bool) $reserva->transferenciaEnRevision() => 'Hay una transferencia en revisión. Espera a que recepción la revise.',
            default                                    => 'Esta reserva ya está pagada por completo.',
        };
    }

    /**
     * Registra un pago (efectivo, tarjeta o QR). Devuelve el pago guardado: APROBADO o RECHAZADO
     * (el rechazo también queda guardado como historial). Si se aprueba, emite su recibo.
     *
     * @param string|null $ultimos4  solo los 4 últimos dígitos de la tarjeta (nunca el número ni el CVV)
     * @param array|null  $respuesta respuesta del banco ya recibida (webhook del QR): no se vuelve a cobrar
     * @param string|null $marca     Visa / Mastercard (solo para mostrarla)
     */
    public static function registrar(Reserva $reserva, Usuario $quien, string $metodo, float $monto,
                                     ?string $ultimos4 = null, ?array $respuesta = null, ?string $marca = null): Pago
    {
        $monto = round($monto, 2);

        if ($metodo === MetodoPago::EFECTIVO && $quien->rol !== Usuario::RECEPCIONISTA) {
            throw ValidationException::withMessages(['metodo' => 'El efectivo solo se paga en la recepción del hotel.']);
        }
        if ($metodo === MetodoPago::TARJETA && ! preg_match('/^\d{4}$/', (string) $ultimos4)) {
            throw ValidationException::withMessages(['tarjeta_numero' => 'Falta el número de la tarjeta.']);
        }

        return DB::transaction(function () use ($reserva, $quien, $metodo, $monto, $ultimos4, $respuesta, $marca) {
            // Bloquear la reserva: dos pagos a la vez no pueden pasarse del saldo
            $reserva = Reserva::whereKey($reserva->id)->lockForUpdate()->with('estado', 'habitaciones')->firstOrFail();

            self::validarMonto($reserva, $monto);

            // Efectivo: aprobado al instante. Tarjeta: lo decide la pasarela simulada.
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
                    $ultimos4 ? trim('Tarjeta ' . $marca) . " terminada en {$ultimos4}." : null,
                    $aprobado ? null : ($respuesta['motivo'] ?? 'Pago rechazado.'),
                ]))) ?: null,
                'registrado_por_usuario_id' => $quien->id,
            ]);

            if ($aprobado) {
                self::alAprobar($reserva, $pago, $quien);
            }

            return $pago;
        });
    }

    // ---------- Transferencia ----------

    /**
     * El cliente o la agencia avisan una transferencia: queda PENDIENTE ("en revisión").
     * El comprobante se guarda en storage/app/private (no es público).
     */
    public static function registrarTransferencia(Reserva $reserva, Usuario $quien, float $monto,
                                                  string $numeroOperacion, UploadedFile $comprobante): Pago
    {
        $monto = round($monto, 2);
        $ruta  = $comprobante->store("comprobantes/{$reserva->id}", 'local');

        try {
            return DB::transaction(function () use ($reserva, $quien, $monto, $numeroOperacion, $ruta) {
                $reserva = Reserva::whereKey($reserva->id)->lockForUpdate()->with('estado', 'habitaciones')->firstOrFail();

                self::validarMonto($reserva, $monto);

                $pago = Pago::create([
                    'reserva_id'                => $reserva->id,
                    'metodo_pago_id'            => MetodoPago::id(MetodoPago::TRANSFERENCIA),
                    'monto'                     => $monto,
                    'estado'                    => Pago::PENDIENTE,
                    'registrado_por_usuario_id' => $quien->id,
                ]);

                Transferencia::create([
                    'pago_id'          => $pago->id,
                    'numero_operacion' => $numeroOperacion,
                    'comprobante'      => $ruta,
                ]);

                return $pago;
            });
        } catch (Throwable $e) {
            Storage::disk('local')->delete($ruta);
            throw $e;
        }
    }

    // Recepción aprueba: confirma la reserva (y su comisión) y emite el recibo
    public static function aprobarTransferencia(Pago $pago, Usuario $recepcionista): Pago
    {
        return DB::transaction(function () use ($pago, $recepcionista) {
            $reserva = Reserva::whereKey($pago->reserva_id)->lockForUpdate()->with('estado', 'habitaciones')->firstOrFail();
            $pago    = Pago::whereKey($pago->id)->lockForUpdate()->with('transferencia')->firstOrFail();

            if ($pago->estado !== Pago::PENDIENTE || ! $pago->transferencia) {
                throw ValidationException::withMessages(['pago' => 'Esta transferencia ya fue revisada.']);
            }
            if (! $reserva->sePuedePagar()) {
                throw ValidationException::withMessages(['pago' => 'La reserva ya no admite pagos: rechaza la transferencia.']);
            }
            if ($pago->monto > $reserva->saldo()) {
                throw ValidationException::withMessages(['pago' => 'El monto pasa el saldo de la reserva: rechaza la transferencia.']);
            }

            $pago->update([
                'estado'     => Pago::APROBADO,
                'referencia' => 'TRF-' . $pago->transferencia->numero_operacion,
            ]);
            $pago->transferencia->update([
                'revisado_por_usuario_id' => $recepcionista->id,
                'fecha_revision'          => now(),
            ]);

            self::alAprobar($reserva, $pago, $recepcionista);

            return $pago;
        });
    }

    public static function rechazarTransferencia(Pago $pago, Usuario $recepcionista, string $motivo): Pago
    {
        return DB::transaction(function () use ($pago, $recepcionista, $motivo) {
            $pago = Pago::whereKey($pago->id)->lockForUpdate()->with('transferencia')->firstOrFail();

            if ($pago->estado !== Pago::PENDIENTE || ! $pago->transferencia) {
                throw ValidationException::withMessages(['pago' => 'Esta transferencia ya fue revisada.']);
            }

            $pago->update(['estado' => Pago::RECHAZADO, 'observacion' => "Transferencia rechazada: {$motivo}"]);
            $pago->transferencia->update([
                'revisado_por_usuario_id' => $recepcionista->id,
                'fecha_revision'          => now(),
                'motivo_rechazo'          => $motivo,
            ]);

            return $pago;
        });
    }

    // Después de aprobar un pago: confirmar si se completó el adelanto, y emitir el recibo
    private static function alAprobar(Reserva $reserva, Pago $pago, Usuario $quien): void
    {
        if ($reserva->esEstado(EstadoReserva::PENDIENTE) && $reserva->totalPagado() >= $reserva->adelanto()) {
            Reservas::confirmar($reserva, $quien);
        }

        Recibos::emitir($pago);
    }
}
