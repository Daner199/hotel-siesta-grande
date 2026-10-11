<?php

namespace App\Support;

/**
 * El sistema trabaja solo en bolivianos.
 */
class Moneda
{
    // Formato boliviano a número: "1.250,50" → "1250.50" · "250,5" → "250.5" · "1.250" → "1250"
    // Devuelve el texto listo para validar como número (null si está vacío)
    public static function leer(mixed $texto): ?string
    {
        $valor = preg_replace('/\s+/', '', (string) $texto);

        if (str_contains($valor, ',')) {
            $valor = str_replace(['.', ','], ['', '.'], $valor);
        } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $valor)) {
            $valor = str_replace('.', '', $valor); // el punto era separador de miles
        }

        return $valor === '' ? null : $valor;
    }

    // 716.25 → "Setecientos dieciséis 25/100 bolivianos" (para el recibo; usa la extensión intl)
    public static function enLetras(int|float|string $monto): string
    {
        $centavos = (int) round((float) $monto * 100);
        $enteros  = intdiv($centavos, 100);
        $letras   = (new \NumberFormatter('es', \NumberFormatter::SPELLOUT))->format($enteros);

        return mb_strtoupper(mb_substr($letras, 0, 1)) . mb_substr($letras, 1)
            . ' ' . str_pad((string) ($centavos % 100), 2, '0', STR_PAD_LEFT) . '/100 bolivianos';
    }

    // 1250.5 → "Bs 1.250,50"
    public static function formato(int|float|string|null $monto): string
    {
        return 'Bs ' . number_format((float) $monto, 2, ',', '.');
    }
}
