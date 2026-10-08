<?php

namespace App\Http\Controllers;

use App\Models\Beneficio;
use App\Models\EstadoHabitacion;
use App\Models\Habitacion;
use App\Models\Hotel;
use App\Models\Promocion;
use App\Models\SalonEvento;
use App\Models\TipoHabitacion;
use App\Support\Disponibilidad;
use App\Support\Moneda;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Página pública del hotel. TODO sale de la base de datos:
 * datos del hotel, tipos, fotos, tarifas, beneficios, promociones y salones.
 * Lo que no tiene datos (promociones, salones) simplemente no se muestra.
 */
class LandingController extends Controller
{
    public const MAX_NOCHES = 90;

    public function index()
    {
        $hotel = Hotel::datos();

        // Tipos activos que tienen habitaciones, con su galería y tarifa de hoy
        $tipos = TipoHabitacion::query()
            ->where('activo', true)
            ->withCount('habitaciones')
            ->with('fotos')
            ->orderBy('id')
            ->get()
            ->filter(fn ($t) => $t->habitaciones_count > 0)
            ->each(function ($t) {
                $t->setRelation('vigente', $t->tarifaVigente());
                // La principal primero
                $t->setRelation('fotos', $t->fotos->sortByDesc('es_principal')->values());
            })
            ->values();

        $promociones = Promocion::vigentes()
            ->with([
                'beneficios' => fn ($q) => $q->where('activo', true)->orderBy('beneficio.id'),
                'tipos'      => fn ($q) => $q->where('activo', true)->orderBy('tipo_habitacion.id'),
            ])
            ->orderBy('fecha_desde')
            ->get();

        return view('landing.index', [
            'hotel'        => $hotel,
            'tipos'        => $tipos,
            'habitaciones' => Habitacion::count(),
            'pisos'        => Habitacion::distinct()->count('piso'),
            'anios'        => (int) Carbon::parse($hotel['desde'])->diffInYears(today()),
            'beneficios'   => Beneficio::where('activo', true)->orderBy('id')->get(),
            'promociones'  => $promociones,
            'salones'      => SalonEvento::where('activo', true)->with('fotos')->orderBy('nombre')->get(),
            'precioDesde'  => $tipos->map(fn ($t) => $t->vigente?->precio_noche)->filter()->min(),
            'usuario'      => auth()->user(),
        ]);
    }

    /**
     * GET /disponibilidad?llegada=AAAA-MM-DD&salida=AAAA-MM-DD&tipo=ID (tipo opcional)
     * Responde JSON con las habitaciones libres por tipo y el total de la estadía en Bs.
     */
    public function disponibilidad(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'llegada' => ['bail', 'required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'salida'  => ['bail', 'required', 'date_format:Y-m-d', 'after:llegada'],
            'tipo'    => ['nullable', 'integer', Rule::exists('tipo_habitacion', 'id')->where('activo', true)],
        ], [
            'llegada.required'       => 'Elige la fecha de llegada.',
            'llegada.date_format'    => 'La fecha de llegada no es válida.',
            'llegada.after_or_equal' => 'La llegada no puede ser en el pasado.',
            'salida.required'        => 'Elige la fecha de salida.',
            'salida.date_format'     => 'La fecha de salida no es válida.',
            'salida.after'           => 'La salida debe ser después de la llegada.',
            'tipo.*'                 => 'Elige un tipo de habitación de la lista.',
        ]);

        $llegada = Carbon::parse($datos['llegada']);
        $salida  = Carbon::parse($datos['salida']);
        $noches  = (int) $llegada->diffInDays($salida);

        if ($noches > self::MAX_NOCHES) {
            return response()->json([
                'message' => 'Puedes consultar hasta ' . self::MAX_NOCHES . ' noches. Para estadías más largas, escríbenos.',
                'errors'  => ['salida' => ['Máximo ' . self::MAX_NOCHES . ' noches.']],
            ], 422);
        }

        $libres = Disponibilidad::libresPorTipo($llegada, $salida);

        $tipos = TipoHabitacion::query()
            ->where('activo', true)
            ->when($datos['tipo'] ?? null, fn ($q, $id) => $q->whereKey($id))
            ->whereHas('habitaciones', fn ($q) => $q->whereHas('estado', fn ($e) => $e->where('nombre', EstadoHabitacion::ACTIVA)))
            ->with('fotoPrincipal')
            ->orderBy('id')
            ->get();

        $resultados = [];

        foreach ($tipos as $tipo) {
            $tarifa = $tipo->tarifaVigente($llegada);
            if (! $tarifa) {
                continue; // sin precio ese día: no se puede ofrecer
            }

            $estadia = Disponibilidad::calcularEstadia((float) $tarifa->precio_noche, $noches);

            $resultados[] = [
                'tipo_id'      => $tipo->id,
                'nombre'       => $tipo->nombreVisible(),
                'capacidad'    => $tipo->capacidad,
                'libres'       => $libres[$tipo->id] ?? 0,
                'foto'         => $tipo->fotoPrincipal?->url(),
                // Sin operaciones: solo lo que ve el huésped
                'estadia_larga' => $estadia['estadia_larga'],
                'sin_descuento' => $estadia['estadia_larga'] ? Moneda::formato($estadia['sin_descuento']) : null,
                'total'         => Moneda::formato($estadia['total']),
                'promedio'      => Moneda::formato($estadia['promedio']),
            ];
        }

        return response()->json([
            'noches'     => $noches,
            'llegada'    => $llegada->locale('es')->isoFormat('ddd D [de] MMM'),
            'salida'     => $salida->locale('es')->isoFormat('ddd D [de] MMM'),
            'resultados' => $resultados,
        ]);
    }
}
