<?php

namespace App\Http\Controllers\Recepcion;

use App\Http\Controllers\ReservaBaseController;
use App\Models\EstadoHabitacion;
use App\Models\Reserva;
use App\Models\Usuario;
use App\Support\Reservas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Reservas desde recepción: para cualquier persona, eligiendo las habitaciones exactas.
 * Ve todas las reservas del hotel. Puede editar titular, personas y observación
 * (cambiar fechas o habitaciones = cancelar y crear otra).
 */
class ReservaController extends ReservaBaseController
{
    protected function prefijo(): string
    {
        return 'recepcion.reservas';
    }

    protected function inicio(): string
    {
        return 'recepcion.inicio';
    }

    protected function misReservas(): Builder
    {
        return Reserva::query();
    }

    // La reserva es de un cliente solo si se indica su correo (se resuelve en store)
    protected function titular(array $datos): array
    {
        return [];
    }

    // Nueva reserva eligiendo los números de habitación
    public function create(Request $request)
    {
        $tipos = $this->tiposReservables()->load([
            'habitaciones' => fn ($q) => $q
                ->whereHas('estado', fn ($e) => $e->where('nombre', EstadoHabitacion::ACTIVA))
                ->orderByRaw('LENGTH(numero), numero'),
        ]);

        return view('reservas.crear', [
            'modo'    => 'habitaciones',
            'tipos'   => $tipos,
            'inicial' => [
                'llegada' => $this->fechaDeConsulta($request, 'llegada'),
                'salida'  => $this->fechaDeConsulta($request, 'salida'),
            ],
        ] + $this->datosVista());
    }

    public function store(Request $request)
    {
        $this->prepararTitular($request);
        $request->merge(['correo_cliente' => Str::lower(trim((string) $request->input('correo_cliente'))) ?: null]);

        $datos = $request->validate($this->reglas() + [
            'correo_cliente'  => ['nullable', 'string', 'max:150', 'email:rfc'],
            'habitaciones'    => ['required', 'array', 'min:1'],
            'habitaciones.*'  => ['integer'],
        ], $this->mensajes() + [
            'correo_cliente.email' => 'Escribe el correo completo, por ejemplo nombre@gmail.com.',
            'correo_cliente.max'   => 'El correo es demasiado largo.',
            'habitaciones.required' => 'Elige al menos una habitación.',
            'habitaciones.min'      => 'Elige al menos una habitación.',
            'habitaciones.*'        => 'Una de las habitaciones elegidas no es válida.',
        ]);

        // Si tiene cuenta de cliente, la reserva aparece en su panel
        if ($datos['correo_cliente']) {
            $cliente = Usuario::where('email', $datos['correo_cliente'])
                ->where('rol', Usuario::CLIENTE)
                ->where('activo', true)
                ->first();

            if (! $cliente) {
                throw ValidationException::withMessages([
                    'correo_cliente' => 'No hay un cliente activo con ese correo. Déjalo vacío si no tiene cuenta.',
                ]);
            }
            $datos['cliente_id'] = $cliente->id;
        }

        $reserva = Reservas::crear($datos, $datos['habitaciones'], $request->user());

        return $this->guardada($reserva);
    }

    // ---------- Editar titular, personas y observación ----------

    public function edit(Reserva $reserva)
    {
        $reserva->load(['estado', 'habitaciones.habitacion.tipo']);

        if (! $reserva->sePuedeCancelar()) {
            return redirect()->route('recepcion.reservas.show', $reserva)
                ->with('error', 'Una reserva ' . mb_strtolower($reserva->estado->etiqueta()) . ' ya no se puede editar.');
        }

        return view('reservas.editar', ['reserva' => $reserva] + $this->datosVista());
    }

    public function update(Request $request, Reserva $reserva)
    {
        $reserva->load(['estado', 'habitaciones.habitacion.tipo']);

        if (! $reserva->sePuedeCancelar()) {
            return redirect()->route('recepcion.reservas.show', $reserva)
                ->with('error', 'Una reserva ' . mb_strtolower($reserva->estado->etiqueta()) . ' ya no se puede editar.');
        }

        $this->prepararTitular($request);

        $reglas = array_intersect_key($this->reglas(), array_flip(['nombre_titular', 'cantidad_personas', 'observacion']));
        $datos  = $request->validate($reglas, $this->mensajes());

        $capacidad = $reserva->habitaciones->sum(fn ($rh) => $rh->habitacion->tipo->capacidad);
        if ((int) $datos['cantidad_personas'] > $capacidad) {
            throw ValidationException::withMessages([
                'cantidad_personas' => 'Sus habitaciones son para ' . $capacidad
                    . ($capacidad === 1 ? ' persona' : ' personas') . ' como máximo.',
            ]);
        }

        $reserva->update($datos);

        return redirect()->route('recepcion.reservas.show', $reserva)
            ->with('exito', "Se guardaron los cambios de la reserva {$reserva->codigo}.");
    }
}
