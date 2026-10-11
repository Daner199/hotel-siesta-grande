<?php

namespace App\Support;

use App\Models\Hotel;
use App\Models\Pago;
use App\Models\Recibo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Recibos de pago (no son facturas: la factura la autoriza Impuestos Nacionales).
 * Uno por cada pago aprobado, numerados SIN saltos: REC-000001, REC-000002…
 */
class Recibos
{
    // Número cualquiera para el candado de PostgreSQL que ordena la numeración
    private const CANDADO = 50510001;

    /**
     * Emite el recibo de un pago aprobado (si ya tiene, devuelve el mismo).
     *
     * @param float|null $saldo saldo después del pago; null = el saldo actual de la reserva
     */
    public static function emitir(Pago $pago, ?float $saldo = null): Recibo
    {
        return DB::transaction(function () use ($pago, $saldo) {
            // Un solo recibo a la vez toma el número siguiente (así no hay saltos ni repetidos)
            DB::select('SELECT pg_advisory_xact_lock(?)', [self::CANDADO]);

            if ($existente = Recibo::where('pago_id', $pago->id)->first()) {
                return $existente;
            }

            $reserva = $pago->reserva()->with('habitaciones')->first();

            return Recibo::create([
                'numero'             => (int) Recibo::max('numero') + 1,
                'pago_id'            => $pago->id,
                'saldo_pendiente'    => $saldo ?? $reserva->saldo(),
                'token_verificacion' => Str::random(40),
            ]);
        });
    }

    /**
     * Todo lo que necesita la vista del recibo (pantalla y PDF).
     */
    public static function datosVista(Recibo $recibo): array
    {
        $recibo->loadMissing([
            'pago.reserva.habitaciones.habitacion', 'pago.reserva.agencia', 'pago.reserva.cliente',
            'pago.metodo', 'pago.transferencia', 'pago.registradoPor',
        ]);

        $hotel = Hotel::datos();

        // Logo como data URI (dompdf no lee URLs). JPG/PNG necesitan la extensión GD.
        $logo = null;
        $ruta = $hotel['rutas']['logo'] ?? null;
        if ($ruta && extension_loaded('gd') && preg_match('/\.(jpe?g|png)$/i', $ruta)) {
            $tipo = str_ends_with(strtolower($ruta), 'png') ? 'png' : 'jpeg';
            $logo = "data:image/{$tipo};base64," . base64_encode(Storage::disk('public')->get($ruta));
        }

        return [
            'recibo' => $recibo,
            'hotel'  => $hotel,
            'logo'   => $logo,
            'qr'     => Qr::dataUri($recibo->urlVerificacion()),
        ];
    }

    // "/recibos/verificar/abc" → "http://192.168.1.50:8000/recibos/verificar/abc"
    // (PAGOS_URL_PUBLICA si está configurada; si no, la dirección con la que se abrió la página)
    public static function urlPublica(string $ruta): string
    {
        $base = config('pagos.url_publica') ?: request()->getSchemeAndHttpHost();

        return rtrim($base, '/') . '/' . ltrim($ruta, '/');
    }

    /**
     * Recibos de los pagos aprobados (o ya devueltos) que todavía no tienen.
     * Por orden de fecha, con el saldo que tenía la reserva en ese momento.
     * Lo usa "php artisan recibos:generar".
     */
    public static function generarFaltantes(): int
    {
        $pagos = Pago::whereIn('estado', [Pago::APROBADO, Pago::DEVUELTO])
            ->whereDoesntHave('recibo')
            ->with('reserva.habitaciones')
            ->orderBy('fecha_pago')->orderBy('id')
            ->get();

        foreach ($pagos as $pago) {
            // Lo pagado hasta ese pago (incluido), contando los que después se devolvieron
            $pagadoHastaEntonces = Pago::where('reserva_id', $pago->reserva_id)
                ->whereIn('estado', [Pago::APROBADO, Pago::DEVUELTO])
                ->where(fn ($q) => $q->where('fecha_pago', '<', $pago->fecha_pago)
                    ->orWhere(fn ($w) => $w->where('fecha_pago', $pago->fecha_pago)->where('id', '<=', $pago->id)))
                ->sum('monto');

            self::emitir($pago, max(0, round($pago->reserva->total() - (float) $pagadoHastaEntonces, 2)));
        }

        return $pagos->count();
    }
}
