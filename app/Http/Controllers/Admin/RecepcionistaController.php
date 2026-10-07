<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Support\Paises;
use App\Support\ValidacionUsuario;
use Illuminate\Http\Request;

class RecepcionistaController extends Controller
{
    // Lista con buscador, filtro por estado y paginación

    public function index(Request $request)
    {
        $buscar = trim((string) $request->query('buscar'));
        $estado = $request->query('estado'); // 'activos', 'inactivos' o vacío

        // Escapar % y _ para que se busquen como texto normal
        $texto   = addcslashes($buscar, '%_\\');
        $digitos = preg_replace('/\D/', '', $buscar);

        $recepcionistas = Usuario::query()
            ->where('rol', Usuario::RECEPCIONISTA)
            ->when($buscar !== '', function ($q) use ($texto, $digitos) {
                $q->where(function ($w) use ($texto, $digitos) {
                    // Nombre completo, sin tildes
                    $w->whereRaw(
                        "unaccent(CONCAT(nombre, ' ', COALESCE(apellido, ''))) ILIKE unaccent(?)",
                        ["%{$texto}%"]
                    )
                    // Correo
                    ->orWhereRaw('unaccent(email) ILIKE unaccent(?)', ["%{$texto}%"]);

                    // Teléfono (si escribió al menos 3 dígitos)
                    if (strlen($digitos) >= 3) {
                        $w->orWhere('telefono', 'LIKE', "%{$digitos}%");
                    }
                });
            })
            ->when($estado === 'activos', fn ($q) => $q->where('activo', true))
            ->when($estado === 'inactivos', fn ($q) => $q->where('activo', false))
            ->orderByDesc('activo')
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        $totales = [
            'todos'   => Usuario::where('rol', Usuario::RECEPCIONISTA)->count(),
            'activos' => Usuario::where('rol', Usuario::RECEPCIONISTA)->where('activo', true)->count(),
        ];

        return view('admin.recepcionistas.index', compact('recepcionistas', 'buscar', 'estado', 'totales'));
    }

    // Formulario vacío
    public function create()
    {
        return view('admin.recepcionistas.formulario', [
            'recepcionista' => null,
            'paises'        => Paises::lista(),
            'telefono'      => Paises::separarTelefono(null),
        ]);
    }

    // Guardar nuevo
    public function store(Request $request)
    {
        ValidacionUsuario::preparar($request);

        $datos = $request->validate(
            ValidacionUsuario::reglas(),
            ValidacionUsuario::mensajes()
        );

        $recepcionista = Usuario::create([
            'nombre'   => $datos['nombre'],
            'apellido' => $datos['apellido'],
            'telefono' => ValidacionUsuario::telefonoE164($datos['telefono'] ?? null, $datos['telefono_pais'] ?? null),
            'email'    => $datos['email'],
            'password' => $datos['password'],
            'rol'      => Usuario::RECEPCIONISTA,   // fijo, nunca del formulario
            'activo'   => true,
        ]);

        return redirect()
            ->route('admin.recepcionistas.index')
            ->with('exito', "Se creó la cuenta de {$recepcionista->nombreCompleto()}.");
    }

    // Formulario con los datos actuales
    public function edit(Usuario $recepcionista)
    {
        $this->asegurarRecepcionista($recepcionista);

        return view('admin.recepcionistas.formulario', [
            'recepcionista' => $recepcionista,
            'paises'        => Paises::lista(),
            'telefono'      => Paises::separarTelefono($recepcionista->telefono),
        ]);
    }

    // Guardar cambios
    public function update(Request $request, Usuario $recepcionista)
    {
        $this->asegurarRecepcionista($recepcionista);

        ValidacionUsuario::preparar($request);

        $datos = $request->validate(
            ValidacionUsuario::reglas(ignorarId: $recepcionista->id, passwordObligatoria: false),
            ValidacionUsuario::mensajes()
        );

        $recepcionista->fill([
            'nombre'   => $datos['nombre'],
            'apellido' => $datos['apellido'],
            'telefono' => ValidacionUsuario::telefonoE164($datos['telefono'] ?? null, $datos['telefono_pais'] ?? null),
            'email'    => $datos['email'],
        ]);

        // La contraseña solo cambia si se escribió una nueva
        if (! empty($datos['password'])) {
            $recepcionista->password = $datos['password'];
        }

        $recepcionista->save();

        return redirect()
            ->route('admin.recepcionistas.index')
            ->with('exito', "Se guardaron los cambios de {$recepcionista->nombreCompleto()}.");
    }

    // Activar o desactivar
    public function cambiarEstado(Usuario $recepcionista)
    {
        $this->asegurarRecepcionista($recepcionista);

        $recepcionista->activo = ! $recepcionista->activo;
        $recepcionista->save();

        $mensaje = $recepcionista->activo
            ? "{$recepcionista->nombreCompleto()} puede volver a iniciar sesión."
            : "{$recepcionista->nombreCompleto()} ya no puede iniciar sesión.";

        return back()->with('exito', $mensaje);
    }

    // Evita que desde esta sección se edite a un cliente, agencia o admin cambiando el id en la URL
    private function asegurarRecepcionista(Usuario $usuario): void
    {
        abort_unless($usuario->rol === Usuario::RECEPCIONISTA, 404);
    }
}