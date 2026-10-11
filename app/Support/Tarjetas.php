<?php

namespace App\Support;

/**
 * Validación de tarjetas (pasarela simulada). El número completo solo existe durante la petición:
 * nunca se guarda ni se envía; del pago quedan solo la marca y los 4 últimos dígitos.
 *
 * Tarjetas de prueba (ver README):
 *   4242 4242 4242 4242  Visa       → aprobada
 *   5555 5555 5555 4444  Mastercard → aprobada
 *   4000 0000 0000 0002  Visa       → rechazada por fondos insuficientes
 *   4000 0000 0000 3220  Visa       → pide 3-D Secure (código por SMS)
 */
class Tarjetas
{
    // Algoritmo de Luhn (el dígito verificador de las tarjetas)
    public static function luhn(string $numero): bool
    {
        if (! preg_match('/^\d{13,16}$/', $numero)) {
            return false;
        }

        $suma = 0;
        foreach (array_reverse(str_split($numero)) as $i => $d) {
            $d = (int) $d;
            if ($i % 2 === 1) {
                $d *= 2;
                if ($d > 9) {
                    $d -= 9;
                }
            }
            $suma += $d;
        }

        return $suma % 10 === 0;
    }

    // Visa: empieza con 4 · Mastercard: 51–55 o 2221–2720
    public static function marca(string $numero): ?string
    {
        return match (true) {
            str_starts_with($numero, '4') => 'Visa',
            (bool) preg_match('/^(5[1-5]|222[1-9]|22[3-9]\d|2[3-6]\d{2}|27[01]\d|2720)/', $numero) => 'Mastercard',
            default => null,
        };
    }

    // La tarjeta de prueba que termina en 3220 pide verificación 3-D Secure
    public static function pide3ds(string $numero): bool
    {
        return str_ends_with($numero, (string) config('pagos.tarjeta_3ds'));
    }
}
