<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TarifaHabitacion;
use App\Models\TipoHabitacion;
use App\Support\Moneda;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Tarifas de un tipo de habitación.
 * - No se editan ni se borran: un cambio de precio es una tarifa nueva.
 * - Una tarifa nueva empieza hoy o después, y después de la última tarifa activa.
 * - Al crearla se cierra la anterior; al anular una programada se vuelve a abrir la anterior.
 * PostgreSQL (EXCLUDE no_solapamiento_tarifas) impide de todos modos tarifas solapadas.
 */
class TarifaController extends Controller
{
    // Tarifa vigente, formulario e historial (línea de tiempo)
    public function index(TipoHabitacion $tipo)
    {
        $tarifas = $tipo->tarifas()->orderBy('fecha_desde')->orderBy('id')->get();

        $ultima = $tipo->ultimaTarifa();

        // Primer día permitido para una tarifa nueva
        $fechaMinima = $ultima && $ultima->fecha_desde->gte(today())
            ? $ultima->fecha_desde->copy()->addDay()
            : today();

        return view('admin.tipos.tarifas', [
            'tipo'         => $tipo,
            'habitaciones' => $tipo->habitaciones()->count(),
            'vigente'      => $tipo->tarifaVigente(),
            'activas'      => $tarifas->where('activa', true)->values(),
            'anuladas'     => $tarifas->where('activa', false)->values(),
            'fechaMinima'  => $fechaMinima,
        ]);
    }

    // Programar una tarifa nueva
    public function store(Request $request, TipoHabitacion $tipo)
    {
        $this->preparar($request);

        $datos = $request->validate([
            'precio_noche' => ['bail', 'required', 'numeric', 'decimal:0,2', 'min:1', 'max:99999.99'],
            'fecha_desde'  => ['bail', 'required', 'date_format:Y-m-d', 'after_or_equal:today'],
        ], [
            'precio_noche.required' => 'Escribe el precio por noche.',
            'precio_noche.numeric'  => 'El precio debe ser un número, por ejemplo 250 o 250,50.',
            'precio_noche.decimal'  => 'El precio puede tener como máximo 2 decimales.',
            'precio_noche.min'      => 'El precio debe ser de al menos Bs 1.',
            'precio_noche.max'      => 'El precio es demasiado alto.',
            'fecha_desde.required'       => 'Elige desde qué fecha rige el precio.',
            'fecha_desde.date_format'    => 'La fecha no es válida.',
            'fecha_desde.after_or_equal' => 'La tarifa no puede empezar en el pasado.',
        ]);

        $desde = Carbon::parse($datos['fecha_desde']);

        DB::transaction(function () use ($tipo, $datos, $desde) {
            // Bloquear el tipo: si dos admins guardan a la vez, el segundo espera
            TipoHabitacion::whereKey($tipo->id)->lockForUpdate()->first();

            $ultima = $tipo->ultimaTarifa();

            if ($ultima && $desde->lte($ultima->fecha_desde)) {
                throw ValidationException::withMessages([
                    'fecha_desde' => 'Debe empezar después del ' . $ultima->fecha_desde->format('d/m/Y')
                        . ', cuando empieza la última tarifa. Si quieres cambiar una tarifa programada, anúlala primero.',
                ]);
            }

            // Cerrar la anterior el día que empieza la nueva
            if ($ultima && ($ultima->fecha_hasta === null || $ultima->fecha_hasta->gt($desde))) {
                $ultima->update(['fecha_hasta' => $desde]);
            }

            $tipo->tarifas()->create([
                'fecha_desde'  => $desde,
                'fecha_hasta'  => null,
                'precio_noche' => $datos['precio_noche'],
                'activa'       => true,
            ]);
        });

        $cuando = $desde->isToday() ? 'Desde hoy' : 'Desde el ' . $desde->format('d/m/Y');

        return redirect()
            ->route('admin.tipos.tarifas', $tipo)
            ->with('exito', "{$cuando}, {$tipo->nombre} costará " . Moneda::formato($datos['precio_noche']) . ' por noche.');
    }

    // Anular una tarifa programada (todavía no empezó)
    public function anular(TipoHabitacion $tipo, TarifaHabitacion $tarifa)
    {
        if (! $tarifa->sePuedeAnular()) {
            return back()->with('error', 'Solo se puede anular una tarifa que todavía no empezó.');
        }

        DB::transaction(function () use ($tipo, $tarifa) {
            TipoHabitacion::whereKey($tipo->id)->lockForUpdate()->first();

            // Primero se anula (el EXCLUDE solo mira las activas)...
            $tarifa->update(['activa' => false]);

            // ...y la tarifa anterior se estira hasta donde terminaba la anulada
            $tipo->tarifas()
                ->where('activa', true)
                ->whereDate('fecha_hasta', $tarifa->fecha_desde)
                ->update(['fecha_hasta' => $tarifa->fecha_hasta]);
        });

        return back()->with(
            'exito',
            'Se anuló la tarifa de ' . Moneda::formato($tarifa->precio_noche)
                . ' que empezaba el ' . $tarifa->fecha_desde->format('d/m/Y') . '.'
        );
    }

    // Formato boliviano a número: "1.250,50" → "1250.50" · "250,5" → "250.5" · "1.250" → "1250"
    private function preparar(Request $request): void
    {
        $precio = preg_replace('/\s+/', '', (string) $request->input('precio_noche'));

        if (str_contains($precio, ',')) {
            $precio = str_replace(['.', ','], ['', '.'], $precio);
        } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $precio)) {
            $precio = str_replace('.', '', $precio); // el punto era separador de miles
        }

        $request->merge(['precio_noche' => $precio === '' ? null : $precio]);
    }
}
