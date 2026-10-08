<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TipoHabitacion;
use App\Support\ValidacionUsuario;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Tipos de habitación (SIMPLE, DOBLE...). Las tarifas de cada tipo
 * se manejan en TarifaController.
 */
class TipoHabitacionController extends Controller
{
    // Lista de tipos (son pocos: sin buscador ni paginación)
    public function index()
    {
        $tipos = TipoHabitacion::query()
            ->withCount('habitaciones')
            ->with('fotoPrincipal')
            ->orderByDesc('activo')
            ->orderBy('id')
            ->get();

        // Tarifa de hoy y la próxima programada de cada tipo
        foreach ($tipos as $tipo) {
            $tipo->setRelation('vigente', $tipo->tarifaVigente());
            $tipo->setRelation('programada', $tipo->tarifaProgramada());
        }

        return view('admin.tipos.index', [
            'tipos'        => $tipos,
            'habitaciones' => $tipos->sum('habitaciones_count'),
        ]);
    }

    // Formulario vacío
    public function create()
    {
        return view('admin.tipos.formulario', ['tipo' => null]);
    }

    // Guardar nuevo tipo y llevar al admin a registrar su tarifa
    public function store(Request $request)
    {
        $this->preparar($request);
        $datos = $request->validate($this->reglas(), $this->mensajes());

        $tipo = TipoHabitacion::create($datos + ['activo' => true]);

        return redirect()
            ->route('admin.tipos.tarifas', $tipo)
            ->with('exito', "Se creó el tipo {$tipo->nombre}. Ahora registra su tarifa por noche.");
    }

    // Formulario con los datos actuales
    public function edit(TipoHabitacion $tipo)
    {
        return view('admin.tipos.formulario', ['tipo' => $tipo]);
    }

    // Guardar cambios
    public function update(Request $request, TipoHabitacion $tipo)
    {
        $this->preparar($request);
        $datos = $request->validate($this->reglas($tipo), $this->mensajes());

        $tipo->update($datos);

        return redirect()
            ->route('admin.tipos.index')
            ->with('exito', "Se guardaron los cambios de {$tipo->nombre}.");
    }

    // Activar o desactivar
    public function cambiarEstado(TipoHabitacion $tipo)
    {
        $tipo->activo = ! $tipo->activo;
        $tipo->save();

        $mensaje = $tipo->activo
            ? "{$tipo->nombre} vuelve a estar disponible para reservas."
            : "{$tipo->nombre} ya no se ofrece en reservas nuevas.";

        return back()->with('exito', $mensaje);
    }

    // Borrar: solo si nadie lo usa
    public function destroy(TipoHabitacion $tipo)
    {
        $enUso = $this->usos($tipo);

        if ($enUso) {
            return back()->with('error', $this->mensajeEnUso($tipo, $enUso));
        }

        try {
            $tipo->delete();
        } catch (QueryException $e) {
            // 23503 = violación de clave foránea (por si algo lo empezó a usar justo ahora)
            if ($e->getCode() === '23503') {
                return back()->with('error', "No se puede eliminar {$tipo->nombre} porque está en uso. Desactívalo si ya no se usa.");
            }
            throw $e;
        }

        // Sus fotos se borraron en la BD (ON DELETE CASCADE): ahora los archivos
        Storage::disk('public')->deleteDirectory($tipo->carpetaFotos());

        return redirect()
            ->route('admin.tipos.index')
            ->with('exito', "Se eliminó el tipo {$tipo->nombre}.");
    }

    // ---------- Ayudas ----------

    // Cuántas habitaciones, tarifas y promociones usan este tipo (solo las que tienen alguna)
    private function usos(TipoHabitacion $tipo): array
    {
        return array_filter([
            'habitacion' => $tipo->habitaciones()->count(),
            'tarifa'     => $tipo->tarifas()->count(),
            'promocion'  => DB::table('promocion_tipo_habitacion')
                                ->where('tipo_habitacion_id', $tipo->id)->count(),
        ]);
    }

    // "...porque tiene 8 habitaciones y 1 tarifa. Desactívalo si ya no se usa."
    private function mensajeEnUso(TipoHabitacion $tipo, array $usos): string
    {
        $palabras = [
            'habitacion' => ['habitación', 'habitaciones'],
            'tarifa'     => ['tarifa', 'tarifas'],
            'promocion'  => ['promoción', 'promociones'],
        ];

        $partes = [];
        foreach ($usos as $cosa => $cantidad) {
            $partes[] = $cantidad . ' ' . $palabras[$cosa][$cantidad === 1 ? 0 : 1];
        }

        $ultima = array_pop($partes);
        $lista  = $partes ? implode(', ', $partes) . ' y ' . $ultima : $ultima;

        return "No se puede eliminar {$tipo->nombre} porque tiene {$lista}. Desactívalo si ya no se usa.";
    }

    // ---------- Validación ----------

    // Nombre sin espacios de más y en MAYÚSCULAS, como los tipos existentes
    private function preparar(Request $request): void
    {
        $nombre = ValidacionUsuario::limpiarTexto($request->input('nombre'));

        $request->merge([
            'nombre'      => $nombre === null ? null : mb_strtoupper($nombre),
            'descripcion' => ValidacionUsuario::limpiarTexto($request->input('descripcion')),
        ]);
    }

    private function reglas(?TipoHabitacion $tipo = null): array
    {
        return [
            'nombre' => [
                'bail', 'required', 'string', 'min:3', 'max:50',
                'regex:/^\pL+(?: \pL+)*$/u',
                Rule::unique('tipo_habitacion', 'nombre')->ignore($tipo?->id),
            ],
            'capacidad'   => ['bail', 'required', 'integer', 'between:1,10'],
            'descripcion' => ['nullable', 'string', 'max:500'],
        ];
    }

    private function mensajes(): array
    {
        return [
            'nombre.required' => 'Escribe el nombre del tipo.',
            'nombre.min'      => 'El nombre debe tener al menos 3 letras.',
            'nombre.max'      => 'El nombre es demasiado largo (máximo 50).',
            'nombre.regex'    => 'El nombre solo puede tener letras y espacios.',
            'nombre.unique'   => 'Ya existe un tipo con este nombre.',

            'capacidad.required' => 'Indica cuántas personas caben.',
            'capacidad.integer'  => 'La capacidad debe ser un número entero.',
            'capacidad.between'  => 'La capacidad debe estar entre 1 y 10 personas.',

            'descripcion.max' => 'La descripción no puede pasar de 500 caracteres.',
        ];
    }
}
