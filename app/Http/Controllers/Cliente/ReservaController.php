<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\ReservaBaseController;
use App\Models\Reserva;
use Illuminate\Database\Eloquent\Builder;

/**
 * Reservas del cliente: reserva para sí mismo (él es el titular) y solo ve las suyas.
 * Todo lo demás está en ReservaBaseController.
 */
class ReservaController extends ReservaBaseController
{
    protected function prefijo(): string
    {
        return 'cliente.reservas';
    }

    protected function inicio(): string
    {
        return 'cliente.inicio';
    }

    protected function misReservas(): Builder
    {
        return Reserva::where('cliente_id', auth()->id());
    }

    // El cliente no escribe el titular: es él mismo
    protected function reglasTitular(): array
    {
        return [];
    }

    protected function titular(array $datos): array
    {
        return [
            'nombre_titular' => auth()->user()->nombreCompleto(),
            'cliente_id'     => auth()->id(),
        ];
    }
}
