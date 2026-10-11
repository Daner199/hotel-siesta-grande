<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo de estados de una reserva (se carga por SQL, no se edita desde el sistema).
 */
class EstadoReserva extends Model
{
    public const PENDIENTE  = 'PENDIENTE';
    public const CONFIRMADA = 'CONFIRMADA';
    public const CANCELADA  = 'CANCELADA';
    public const NO_SHOW    = 'NO_SHOW';
    public const CHECK_IN   = 'CHECK_IN';
    public const CHECK_OUT  = 'CHECK_OUT';

    // Estados que ocupan la habitación (los mismos que revisa el trigger de la BD)
    public const OCUPAN = [self::PENDIENTE, self::CONFIRMADA, self::CHECK_IN];

    protected $table = 'estado_reserva';

    public $timestamps = false;

    protected $fillable = ['nombre'];

    // id de un estado por su nombre: EstadoReserva::id('CONFIRMADA')
    public static function id(string $nombre): int
    {
        return (int) static::where('nombre', $nombre)->value('id');
    }

    // "CHECK_IN" → "Check-in" (para mostrar en pantalla)
    public function etiqueta(): string
    {
        return match ($this->nombre) {
            self::PENDIENTE  => 'Pendiente',
            self::CONFIRMADA => 'Confirmada',
            self::CANCELADA  => 'Cancelada',
            self::NO_SHOW    => 'No-show',
            self::CHECK_IN   => 'Check-in',
            self::CHECK_OUT  => 'Check-out',
            default          => $this->nombre,
        };
    }
}
