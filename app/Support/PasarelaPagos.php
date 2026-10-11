<?php

namespace App\Support;

use App\Models\MetodoPago;
use App\Models\Pago;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

/**
 * Pasarela de pagos SIMULADA (requisito 17). Ver config/pagos.php.
 *
 * simular(): la "lógica del banco". La usa la API POST /api/pagos/simular y el modo interno.
 * cobrar():  lo que llama Laravel al pagar. Según PAGOS_API_MODO usa simular() por dentro
 *            o hace la petición HTTP real a la API.
 */
class PasarelaPagos
{
    public const APROBADO  = 'APROBADO';
    public const RECHAZADO = 'RECHAZADO';

    /**
     * Aprueba siempre, salvo la tarjeta de prueba que termina en 0002.
     *
     * @return array{estado: string, codigo?: string, motivo?: string}
     */
    public static function simular(float $monto, string $metodo, ?string $ultimos4 = null): array
    {
        if ($metodo === MetodoPago::TARJETA && $ultimos4 === config('pagos.tarjeta_rechazada')) {
            return ['estado' => self::RECHAZADO, 'motivo' => 'El banco rechazó la tarjeta.'];
        }

        return ['estado' => self::APROBADO, 'codigo' => self::codigoNuevo('PAY')];
    }

    /**
     * Cobra un pago en línea (QR o tarjeta). Nunca recibe el número completo ni el CVV.
     *
     * @return array{estado: string, codigo?: string, motivo?: string}
     */
    public static function cobrar(float $monto, string $metodo, ?string $ultimos4 = null): array
    {
        if (config('pagos.modo') !== 'http') {
            return self::simular($monto, $metodo, $ultimos4);
        }

        try {
            $respuesta = Http::timeout(config('pagos.segundos'))
                ->acceptJson()
                ->withHeaders(['X-Api-Clave' => config('pagos.clave')])
                ->post(config('pagos.url'), array_filter([
                    'monto'    => $monto,
                    'metodo'   => $metodo,
                    'ultimos4' => $ultimos4,
                ]));
        } catch (ConnectionException) {
            throw ValidationException::withMessages([
                'pago' => 'No se pudo conectar con la pasarela de pagos (' . config('pagos.url') . '). '
                    . '¿Está corriendo "php artisan serve --port=8001"? No se cobró nada.',
            ]);
        }

        $estado = $respuesta->json('estado');
        if (! $respuesta->successful() || ! in_array($estado, [self::APROBADO, self::RECHAZADO], true)) {
            throw ValidationException::withMessages([
                'pago' => 'La pasarela de pagos respondió con un error (' . $respuesta->status() . '). No se cobró nada.',
            ]);
        }

        return $respuesta->json();
    }

    // EFE-482913 / PAY-482913, que no exista ya en la tabla pago
    public static function codigoNuevo(string $prefijo): string
    {
        do {
            $codigo = $prefijo . '-' . random_int(100000, 999999);
        } while (Pago::where('referencia', $codigo)->exists());

        return $codigo;
    }
}
