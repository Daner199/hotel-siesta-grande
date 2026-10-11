<?php

namespace App\Support;

use App\Models\CobroQr;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Models\Reserva;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Cobro con QR dinámico (lado del HOTEL).
 *
 * 1. crear(): el hotel genera un cobro (monto fijo, vence en 15 min) y muestra el QR.
 * 2. El celular abre Banco Siesta (App\Support\BancoSiesta) y confirma.
 * 3. El banco avisa por webhook → recibirWebhook(): verifica la firma, registra el pago,
 *    confirma la reserva y emite el recibo.
 * 4. La pantalla de la PC pregunta el estado cada 2 segundos y salta al recibo.
 */
class CobrosQr
{
    public static function crear(Reserva $reserva, Usuario $quien, float $monto): CobroQr
    {
        return DB::transaction(function () use ($reserva, $quien, $monto) {
            $reserva = Reserva::whereKey($reserva->id)->lockForUpdate()->with('estado', 'habitaciones')->firstOrFail();

            Pagos::validarMonto($reserva, round($monto, 2));

            // Un solo QR activo por reserva: el anterior queda anulado
            CobroQr::where('reserva_id', $reserva->id)->where('estado', CobroQr::PENDIENTE)
                ->update(['estado' => CobroQr::ANULADO]);

            return CobroQr::create([
                'token'                 => Str::random(40),
                'reserva_id'            => $reserva->id,
                'monto'                 => round($monto, 2),
                'vence_en'              => now()->addMinutes(config('pagos.qr_minutos')),
                'estado'                => CobroQr::PENDIENTE,
                'creado_por_usuario_id' => $quien->id,
            ]);
        });
    }

    // Si ya pasó la hora, queda VENCIDO (se revisa cada vez que se consulta)
    public static function actualizar(CobroQr $cobro): CobroQr
    {
        if ($cobro->vencido()) {
            $cobro->update(['estado' => CobroQr::VENCIDO]);
        }

        return $cobro;
    }

    public static function anular(CobroQr $cobro): void
    {
        CobroQr::whereKey($cobro->id)->where('estado', CobroQr::PENDIENTE)->update(['estado' => CobroQr::ANULADO]);
    }

    // Dirección que va dentro del QR: la app de Banco Siesta con el token del cobro
    public static function urlBanco(CobroQr $cobro): string
    {
        $base = config('pagos.banco_url') ?: (config('pagos.url_publica') ?: request()->getSchemeAndHttpHost());

        return rtrim($base, '/') . '/banco/qr/' . $cobro->token;
    }

    // Firma HMAC-SHA256 del cuerpo del webhook (la misma clave en el hotel y en el banco)
    public static function firmar(string $cuerpo): string
    {
        $secreto = config('pagos.webhook_secreto') ?: config('app.key');

        return hash_hmac('sha256', $cuerpo, (string) $secreto);
    }

    /**
     * Webhook del banco: POST /api/pagos/webhook (cabecera X-Firma).
     * Cuerpo: {"token": "...", "resultado": "APROBADO|RECHAZADO", "transaccion": "123456789", "monto": 716.25}
     *
     * @return array{estado: int, cuerpo: array}
     */
    public static function recibirWebhook(string $cuerpo, ?string $firma): array
    {
        if (! $firma || ! hash_equals(self::firmar($cuerpo), $firma)) {
            return ['estado' => 401, 'cuerpo' => ['error' => 'Firma inválida.']];
        }

        $datos = json_decode($cuerpo, true);
        if (! is_array($datos) || ! isset($datos['token'], $datos['resultado'], $datos['transaccion'], $datos['monto'])
            || ! in_array($datos['resultado'], [Pago::APROBADO, Pago::RECHAZADO], true)) {
            return ['estado' => 422, 'cuerpo' => ['error' => 'Datos incompletos.']];
        }

        return DB::transaction(function () use ($datos) {
            $cobro = CobroQr::where('token', $datos['token'])->lockForUpdate()->first();
            if (! $cobro) {
                return ['estado' => 404, 'cuerpo' => ['error' => 'El cobro no existe.']];
            }

            $referencia = 'QR-' . $datos['transaccion'];

            // El mismo aviso dos veces (el banco reintenta): no se paga dos veces
            if ($cobro->estado === CobroQr::PAGADO) {
                $repetido = $cobro->pago?->referencia === $referencia;

                return ['estado' => $repetido ? 200 : 409, 'cuerpo' => $repetido
                    ? ['ok' => true, 'estado' => CobroQr::PAGADO, 'repetido' => true]
                    : ['error' => 'El cobro ya fue pagado.']];
            }

            self::actualizar($cobro);
            if ($cobro->estado !== CobroQr::PENDIENTE) {
                return ['estado' => 409, 'cuerpo' => ['error' => $cobro->estado === CobroQr::VENCIDO
                    ? 'El cobro venció.' : 'El cobro ya no está pendiente.']];
            }

            if (round((float) $datos['monto'], 2) !== round((float) $cobro->monto, 2)) {
                return ['estado' => 422, 'cuerpo' => ['error' => 'El monto no coincide con el cobro.']];
            }

            $aprobado  = $datos['resultado'] === Pago::APROBADO;
            $respuesta = $aprobado
                ? ['estado' => PasarelaPagos::APROBADO, 'codigo' => $referencia]
                : ['estado' => PasarelaPagos::RECHAZADO, 'motivo' => 'Fondos insuficientes.'];

            try {
                // Quien generó el QR "confirma": si fue un recepcionista, gana su comisión
                $pago = Pagos::registrar($cobro->reserva, $cobro->creador, MetodoPago::QR, (float) $cobro->monto, null, $respuesta);
            } catch (ValidationException $e) {
                // Mientras se pagaba cambió la reserva (por ejemplo, la pagaron en recepción)
                $cobro->update(['estado' => CobroQr::ANULADO]);

                return ['estado' => 409, 'cuerpo' => ['error' => collect($e->errors())->flatten()->first()]];
            }

            $cobro->update([
                'estado'  => $aprobado ? CobroQr::PAGADO : CobroQr::RECHAZADO,
                'pago_id' => $pago->id,
            ]);

            return ['estado' => 200, 'cuerpo' => ['ok' => true, 'estado' => $cobro->estado]];
        });
    }
}
