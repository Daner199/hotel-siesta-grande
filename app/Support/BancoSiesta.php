<?php

namespace App\Support;

use App\Models\CobroQr;
use App\Models\Pago;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Banco Siesta: banco SIMULADO (lado del BANCO). Su "app" se abre en el celular al escanear el QR.
 * Al confirmar, avisa al hotel por webhook firmado, como un banco real:
 *   - PAGOS_API_MODO=interno: llama al webhook por dentro (mismo servidor).
 *   - PAGOS_API_MODO=http:    POST real a PAGOS_WEBHOOK_URL (el banco corre en el puerto 8001).
 */
class BancoSiesta
{
    /**
     * @param bool $fondosInsuficientes para mostrar un rechazo en la defensa
     * @return array{ok: bool, resultado?: string, transaccion?: string, fecha?: string, error?: string}
     */
    public static function pagar(CobroQr $cobro, bool $fondosInsuficientes = false): array
    {
        $transaccion = (string) random_int(100000000, 999999999);
        $resultado   = $fondosInsuficientes ? Pago::RECHAZADO : Pago::APROBADO;

        $cuerpo = json_encode([
            'token'       => $cobro->token,
            'resultado'   => $resultado,
            'transaccion' => $transaccion,
            'monto'       => (float) $cobro->monto,
            'fecha'       => now()->toIso8601String(),
        ]);
        $firma = CobrosQr::firmar($cuerpo);

        if (config('pagos.modo') === 'http') {
            try {
                $respuesta = Http::timeout(config('pagos.segundos'))
                    ->withHeaders(['X-Firma' => $firma])
                    ->withBody($cuerpo, 'application/json')
                    ->acceptJson()
                    ->post(config('pagos.webhook_url'));
            } catch (ConnectionException) {
                return ['ok' => false, 'error' => 'No se pudo comunicar con el comercio. Intenta de nuevo.'];
            }
            $estado = $respuesta->status();
            $datos  = $respuesta->json() ?? [];
        } else {
            ['estado' => $estado, 'cuerpo' => $datos] = CobrosQr::recibirWebhook($cuerpo, $firma);
        }

        if ($estado !== 200) {
            return ['ok' => false, 'error' => $datos['error'] ?? 'El comercio no aceptó el pago.'];
        }

        return [
            'ok'          => true,
            'resultado'   => $resultado,
            'transaccion' => $transaccion,
            'fecha'       => now()->format('d/m/Y H:i:s'),
        ];
    }
}
