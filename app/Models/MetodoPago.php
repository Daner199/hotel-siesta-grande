<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo de métodos de pago (se carga por SQL, no se edita desde el sistema).
 * El efectivo solo lo registra recepción; en línea: QR o tarjeta (por la pasarela simulada)
 * y transferencia (con comprobante que revisa recepción).
 */
class MetodoPago extends Model
{
    public const EFECTIVO = 'EFECTIVO';
    public const QR       = 'QR';
    public const TARJETA  = 'TARJETA';
    public const TRANSFERENCIA = 'TRANSFERENCIA';

    // Los que pasan por la pasarela simulada
    public const EN_LINEA = [self::QR, self::TARJETA];

    protected $table = 'metodo_pago';

    public $timestamps = false;

    protected $fillable = ['nombre'];

    public static function id(string $nombre): int
    {
        return (int) static::where('nombre', $nombre)->value('id');
    }

    public function etiqueta(): string
    {
        return match ($this->nombre) {
            self::EFECTIVO => 'Efectivo',
            self::QR       => 'QR',
            self::TARJETA  => 'Tarjeta',
            self::TRANSFERENCIA => 'Transferencia',
            default        => $this->nombre,
        };
    }

    public function icono(): string
    {
        return match ($this->nombre) {
            self::EFECTIVO => 'banknote',
            self::QR       => 'qr-code',
            self::TARJETA  => 'credit-card',
            self::TRANSFERENCIA => 'landmark',
            default        => 'wallet',
        };
    }
}
