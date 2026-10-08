<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EstadoHabitacion;
use App\Models\Habitacion;
use App\Models\TipoHabitacion;
use App\Support\ValidacionUsuario;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Habitaciones del hotel. No se borran: si una ya no se usa pasa a FUERA_SERVICIO.
 */
class HabitacionController extends Controller
{
    // Lista con buscador, filtros (tipo, piso, estado) y paginación de 15 (un piso por página)
    public function index(Request $request)
    {
        $buscar = trim((string) $request->query('buscar'));
        $tipoId = $request->integer('tipo') ?: null;
        $piso   = $request->integer('piso') ?: null;
        $estado = $request->integer('estado') ?: null;

        $texto = addcslashes($buscar, '%_\\');

        $habitaciones = Habitacion::query()
            ->with(['tipo', 'estado'])
            ->when($buscar !== '', function ($q) use ($texto) {
                $q->where(function ($w) use ($texto) {
                    $w->where('numero', 'ILIKE', "%{$texto}%")
                      ->orWhereRaw('unaccent(COALESCE(descripcion, \'\')) ILIKE unaccent(?)', ["%{$texto}%"]);
                });
            })
            ->when($tipoId, fn ($q) => $q->where('tipo_habitacion_id', $tipoId))
            ->when($piso, fn ($q) => $q->where('piso', $piso))
            ->when($estado, fn ($q) => $q->where('estado_habitacion_id', $estado))
            ->orderBy('piso')
            ->orderByRaw('LENGTH(numero), numero')   // 101, 102 … 115 (y no 1010 antes de 102)
            ->paginate(15)
            ->withQueryString();

        // Tarifa de hoy de cada tipo (una consulta por tipo, no por habitación)
        $tipos    = TipoHabitacion::orderBy('id')->get();
        $tarifas  = $tipos->mapWithKeys(fn ($t) => [$t->id => $t->tarifaVigente()?->precio_noche]);
        $estados  = EstadoHabitacion::orderBy('id')->get();

        // Resumen por estado para la cabecera
        $resumen = Habitacion::selectRaw('estado_habitacion_id, COUNT(*) AS total')
            ->groupBy('estado_habitacion_id')
            ->pluck('total', 'estado_habitacion_id');

        return view('admin.habitaciones.index', [
            'habitaciones' => $habitaciones,
            'tipos'        => $tipos,
            'tarifas'      => $tarifas,
            'estados'      => $estados,
            'pisos'        => Habitacion::distinct()->orderBy('piso')->pluck('piso'),
            'resumen'      => $resumen,
            'total'        => $resumen->sum(),
            'buscar'       => $buscar,
            'filtros'      => ['tipo' => $tipoId, 'piso' => $piso, 'estado' => $estado],
        ]);
    }

    // Formulario vacío
    public function create()
    {
        return view('admin.habitaciones.formulario', $this->datosFormulario(null));
    }

    // Guardar nueva
    public function store(Request $request)
    {
        $this->preparar($request);
        $datos = $request->validate($this->reglas(), $this->mensajes());

        $habitacion = Habitacion::create($datos);

        return redirect()
            ->route('admin.habitaciones.index')
            ->with('exito', "Se registró la habitación {$habitacion->numero}.");
    }

    // Formulario con los datos actuales
    public function edit(Habitacion $habitacion)
    {
        return view('admin.habitaciones.formulario', $this->datosFormulario($habitacion));
    }

    // Guardar cambios
    public function update(Request $request, Habitacion $habitacion)
    {
        $this->preparar($request);
        $datos = $request->validate($this->reglas($habitacion), $this->mensajes());

        $habitacion->update($datos);

        return redirect()
            ->route('admin.habitaciones.index')
            ->with('exito', "Se guardaron los cambios de la habitación {$habitacion->numero}.");
    }

    // Cambiar solo el estado físico (desde el selector de la lista)
    public function cambiarEstado(Request $request, Habitacion $habitacion)
    {
        $datos = $request->validate([
            'estado_habitacion_id' => ['required', Rule::exists('estado_habitacion', 'id')],
        ], [
            'estado_habitacion_id.*' => 'Elige un estado de la lista.',
        ]);

        $habitacion->update($datos);
        $habitacion->load('estado');

        return back()->with(
            'exito',
            "La habitación {$habitacion->numero} ahora está: {$habitacion->estado->etiqueta()}."
        );
    }

    // ---------- Ayudas ----------

    private function datosFormulario(?Habitacion $habitacion): array
    {
        // Tipos activos + el tipo actual de la habitación aunque esté inactivo
        $tipos = TipoHabitacion::query()
            ->where('activo', true)
            ->when($habitacion, fn ($q) => $q->orWhere('id', $habitacion->tipo_habitacion_id))
            ->orderBy('id')
            ->get();

        return [
            'habitacion' => $habitacion,
            'tipos'      => $tipos,
            'estados'    => EstadoHabitacion::orderBy('id')->get(),
        ];
    }

    // ---------- Validación ----------

    private function preparar(Request $request): void
    {
        $numero = preg_replace('/\s+/', '', (string) $request->input('numero'));

        $request->merge([
            'numero'      => $numero === '' ? null : mb_strtoupper($numero),
            'descripcion' => ValidacionUsuario::limpiarTexto($request->input('descripcion')),
        ]);
    }

    private function reglas(?Habitacion $habitacion = null): array
    {
        return [
            'numero' => [
                'bail', 'required', 'string', 'max:10',
                'regex:/^[0-9A-Z]+(-[0-9A-Z]+)*$/',
                Rule::unique('habitacion', 'numero')->ignore($habitacion?->id),
            ],
            'piso' => ['bail', 'required', 'integer', 'between:1,30'],
            'tipo_habitacion_id' => [
                'bail', 'required',
                // Solo tipos activos (al editar, también vale el tipo que ya tenía)
                Rule::exists('tipo_habitacion', 'id')->where(fn ($q) => $q->where('activo', true)
                    ->when($habitacion, fn ($q2) => $q2->orWhere('id', $habitacion->tipo_habitacion_id))),
            ],
            'estado_habitacion_id' => ['bail', 'required', Rule::exists('estado_habitacion', 'id')],
            'descripcion'          => ['nullable', 'string', 'max:500'],
        ];
    }

    private function mensajes(): array
    {
        return [
            'numero.required' => 'Escribe el número de la habitación.',
            'numero.max'      => 'El número es demasiado largo (máximo 10).',
            'numero.regex'    => 'Usa solo números, letras y guion. Ej.: 101 o 101A.',
            'numero.unique'   => 'Ya existe una habitación con este número.',

            'piso.required' => 'Indica el piso.',
            'piso.integer'  => 'El piso debe ser un número entero.',
            'piso.between'  => 'El piso debe estar entre 1 y 30.',

            'tipo_habitacion_id.required' => 'Elige el tipo de habitación.',
            'tipo_habitacion_id.exists'   => 'Elige un tipo activo de la lista.',

            'estado_habitacion_id.required' => 'Elige el estado.',
            'estado_habitacion_id.exists'   => 'Elige un estado de la lista.',

            'descripcion.max' => 'La descripción no puede pasar de 500 caracteres.',
        ];
    }
}
