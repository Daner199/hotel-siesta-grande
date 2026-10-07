<?php

namespace App\Support;

use Collator;
use Illuminate\Support\Facades\Cache;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Locale;

class Paises
{
    /** ['BO' => 'Bolivia (+591)', ...] en español y orden alfabético */
    public static function lista(): array
    {
        return Cache::rememberForever('paises_telefono_es', function () {
            $util = PhoneNumberUtil::getInstance();
            $paises = [];

            foreach ($util->getSupportedRegions() as $iso) {
                $nombre = Locale::getDisplayRegion('-' . $iso, 'es');
                $codigo = $util->getCountryCodeForRegion($iso);
                $paises[$iso] = "{$nombre} (+{$codigo})";
            }

            $collator = new Collator('es');
            uasort($paises, fn ($a, $b) => $collator->compare($a, $b));

            return $paises;
        });
    }

    /** '+59171234567' → ['pais' => 'BO', 'numero' => '71234567'] */
    public static function separarTelefono(?string $e164): array
    {
        $vacio = ['pais' => 'BO', 'numero' => ''];

        if (! $e164) {
            return $vacio;
        }

        try {
            $util = PhoneNumberUtil::getInstance();
            $numero = $util->parse($e164, null);

            return [
                'pais'   => $util->getRegionCodeForNumber($numero) ?? 'BO',
                'numero' => (string) $numero->getNationalNumber(),
            ];
        } catch (NumberParseException) {
            return $vacio;
        }
    }

    /** '+59171234567' → '+591 7 1234567' (para mostrar) */
    public static function formatearTelefono(?string $e164): ?string
    {
        if (! $e164) {
            return null;
        }

        try {
            $util = PhoneNumberUtil::getInstance();

            return $util->format($util->parse($e164, null), PhoneNumberFormat::INTERNATIONAL);
        } catch (NumberParseException) {
            return $e164;
        }
    }
}