<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BloqueoHabitacion;
use App\Models\Habitacion;
use App\Support\Reservas;
use App\Support\ValidacionUsuario;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Bloqueos temporales de una habitación (mantenimiento...), en la pantalla de editar habitación.
 * Rango [desde, hasta): "hasta" es el día en que vuelve a estar libre.
 * La BD no deja bloquear encima de reservas activas (trigger del script 07).
 */
class BloqueoController extends Controller
{
    public function store(Request $request, Habitacion $habitacion)
    {
        // "  pintura " → "Pintura"
        $motivo = ValidacionUsuario::limpiarTexto($request->input('motivo'));
        $request->merge(['motivo' => $motivo === null ? null : mb_strtoupper(mb_substr($motivo, 0, 1)) . mb_substr($motivo, 1)]);

        $validador = Validator::make($request->all(), [
            'fecha_desde' => ['bail', 'required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'fecha_hasta' => ['bail', 'required', 'date_format:Y-m-d', 'after:fecha_desde'],
            'motivo'      => ['bail', 'required', 'string', 'max:200'],
        ], [
            'fecha_desde.required'       => 'Elige desde qué día se bloquea.',
            'fecha_desde.date_format'    => 'La fecha no es válida.',
            'fecha_desde.after_or_equal' => 'El bloqueo no puede empezar en el pasado.',
            'fecha_hasta.required'       => 'Elige el día en que vuelve a estar libre.',
            'fecha_hasta.date_format'    => 'La fecha no es válida.',
            'fecha_hasta.after'          => 'Debe ser después del inicio del bloqueo.',
            'motivo.required'            => 'Escribe el motivo (ej.: pintura, plomería).',
            'motivo.max'                 => 'El motivo no puede pasar de 200 caracteres.',
        ]);

        // Si hay errores, vuelve a la sección de bloqueos (no al inicio de la página)
        if ($validador->fails()) {
            return redirect()
                ->to(route('admin.habitaciones.edit', $habitacion) . '#bloqueos')
                ->withErrors($validador, 'bloqueo')
                ->withInput();
        }
        $datos = $validador->validated();

        // ¿Se cruza con otro bloqueo de la misma habitación?
        $cruce = $habitacion->bloqueos()
            ->where('fecha_desde', '<', $datos['fecha_hasta'])
            ->where('fecha_hasta', '>', $datos['fecha_desde'])
            ->first();

        if ($cruce) {
            return $this->volverConError(
                $habitacion,
                'Ya tiene un bloqueo del ' . $cruce->fecha_desde->format('d/m/Y')
                    . ' al ' . $cruce->fecha_hasta->format('d/m/Y') . '.'
            );
        }

        try {
            $habitacion->bloqueos()->create($datos);
        } catch (QueryException $e) {
            // 23P01 = la habitación tiene reservas activas en esas fechas
            if ($e->getCode() === '23P01') {
                return $this->volverConError($habitacion, Reservas::mensajeBd($e));
            }
            throw $e;
        }

        $desde = Carbon::parse($datos['fecha_desde'])->format('d/m/Y');
        $hasta = Carbon::parse($datos['fecha_hasta'])->format('d/m/Y');

        return redirect()
            ->to(route('admin.habitaciones.edit', $habitacion) . '#bloqueos')
            ->with('exito', "La habitación {$habitacion->numero} queda bloqueada del {$desde} al {$hasta}.");
    }

    // Quitar un bloqueo en curso o programado (los terminados se conservan como historial)
    public function destroy(Habitacion $habitacion, BloqueoHabitacion $bloqueo)
    {
        if ($bloqueo->fecha_hasta->lte(today())) {
            return back()->with('error', 'Un bloqueo que ya terminó se conserva como historial.');
        }

        $bloqueo->delete();

        return redirect()
            ->to(route('admin.habitaciones.edit', $habitacion) . '#bloqueos')
            ->with('exito', "Se quitó el bloqueo de la habitación {$habitacion->numero}.");
    }

    private function volverConError(Habitacion $habitacion, string $mensaje)
    {
        return redirect()
            ->to(route('admin.habitaciones.edit', $habitacion) . '#bloqueos')
            ->withErrors(['fecha_desde' => $mensaje], 'bloqueo')
            ->withInput();
    }
}
