<?php

namespace App\Http\Controllers\Agencia;

use App\Http\Controllers\ReservaBaseController;
use App\Models\Reserva;
use Illuminate\Database\Eloquent\Builder;

/**
 * Reservas de la agencia: para un titular (su cliente), con 10 % de descuento.
 * Ve todas las reservas de su agencia. Todo lo demás está en ReservaBaseController.
 */
class ReservaController extends ReservaBaseController
{
    protected function prefijo(): string
    {
        return 'agencia.reservas';
    }

    protected function inicio(): string
    {
        return 'agencia.inicio';
    }

    protected function esAgencia(): bool
    {
        return true;
    }

    protected function misReservas(): Builder
    {
        return Reserva::where('agencia_id', auth()->user()->agencia->id);
    }

    // El titular lo escribe la agencia (no es la misma persona que la agencia)
    protected function titular(array $datos): array
    {
        return ['agencia_id' => auth()->user()->agencia->id];
    }
}
