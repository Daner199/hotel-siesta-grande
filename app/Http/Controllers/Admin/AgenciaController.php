<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agencia;
use App\Models\Usuario;
use App\Support\Paises;
use App\Support\ValidacionUsuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Agencias: la EMPRESA va en la tabla agencia,
 * la PERSONA DE CONTACTO (quien inicia sesión) va en usuario.
 */
class AgenciaController extends Controller
{
    // Lista con buscador, filtro y paginación
    public function index(Request $request)
    {
        $buscar = trim((string) $request->query('buscar'));
        $estado = $request->query('estado'); // 'activas', 'inactivas' o vacío

        $texto   = addcslashes($buscar, '%_\\');
        $digitos = preg_replace('/\D/', '', $buscar);

        $agencias = Agencia::query()
            ->join('usuario', 'usuario.id', '=', 'agencia.usuario_id')
            ->select('agencia.*')
            ->with('usuario')
            ->when($buscar !== '', function ($q) use ($texto, $digitos) {
                $q->where(function ($w) use ($texto, $digitos) {
                    // Nombre de la agencia
                    $w->whereRaw('unaccent(agencia.nombre) ILIKE unaccent(?)', ["%{$texto}%"])
                      // Persona de contacto
                      ->orWhereRaw(
                          "unaccent(CONCAT(usuario.nombre, ' ', COALESCE(usuario.apellido, ''))) ILIKE unaccent(?)",
                          ["%{$texto}%"]
                      )
                      // Correo
                      ->orWhereRaw('unaccent(usuario.email) ILIKE unaccent(?)', ["%{$texto}%"]);

                    // NIT y teléfonos (si escribió al menos 3 dígitos)
                    if (strlen($digitos) >= 3) {
                        $w->orWhere('agencia.nit', 'LIKE', "%{$digitos}%")
                          ->orWhere('agencia.telefono', 'LIKE', "%{$digitos}%")
                          ->orWhere('usuario.telefono', 'LIKE', "%{$digitos}%");
                    }
                });
            })
            ->when($estado === 'activas', fn ($q) => $q->where('agencia.activa', true))
            ->when($estado === 'inactivas', fn ($q) => $q->where('agencia.activa', false))
            ->orderByDesc('agencia.activa')
            ->orderBy('agencia.nombre')
            ->paginate(10)
            ->withQueryString();

        $totales = [
            'todas'   => Agencia::count(),
            'activas' => Agencia::where('activa', true)->count(),
        ];

        return view('admin.agencias.index', compact('agencias', 'buscar', 'estado', 'totales'));
    }

    // Formulario vacío
    public function create()
    {
        return view('admin.agencias.formulario', [
            'agencia'          => null,
            'paises'           => Paises::lista(),
            'telefonoAgencia'  => Paises::separarTelefono(null),
            'telefonoContacto' => Paises::separarTelefono(null),
        ]);
    }

    // Guardar nueva agencia (usuario + agencia en una transacción)
    public function store(Request $request)
    {
        $this->preparar($request);
        $datos = $request->validate($this->reglas(), $this->mensajes());

        $agencia = DB::transaction(function () use ($datos) {
            $usuario = Usuario::create([
                'nombre'   => $datos['nombre'],
                'apellido' => $datos['apellido'],
                'telefono' => ValidacionUsuario::telefonoE164($datos['telefono'] ?? null, $datos['telefono_pais'] ?? null),
                'email'    => $datos['email'],
                'password' => $datos['password'],
                'rol'      => Usuario::AGENCIA,   // fijo, nunca del formulario
                'activo'   => true,
            ]);

            return Agencia::create([
                'usuario_id' => $usuario->id,
                'nombre'     => $datos['agencia_nombre'],
                'nit'        => $datos['nit'],
                'telefono'   => ValidacionUsuario::telefonoE164($datos['agencia_telefono'] ?? null, $datos['agencia_telefono_pais'] ?? null),
                'activa'     => true,
            ]);
        });

        return redirect()
            ->route('admin.agencias.index')
            ->with('exito', "Se registró la agencia {$agencia->nombre}.");
    }

    // Formulario con los datos actuales
    public function edit(Agencia $agencia)
    {
        $agencia->load('usuario');

        return view('admin.agencias.formulario', [
            'agencia'          => $agencia,
            'paises'           => Paises::lista(),
            'telefonoAgencia'  => Paises::separarTelefono($agencia->telefono),
            'telefonoContacto' => Paises::separarTelefono($agencia->usuario->telefono),
        ]);
    }

    // Guardar cambios (las dos tablas en una transacción)
    public function update(Request $request, Agencia $agencia)
    {
        $agencia->load('usuario');

        $this->preparar($request);
        $datos = $request->validate($this->reglas($agencia), $this->mensajes());

        DB::transaction(function () use ($agencia, $datos) {
            $usuario = $agencia->usuario;

            $usuario->fill([
                'nombre'   => $datos['nombre'],
                'apellido' => $datos['apellido'],
                'telefono' => ValidacionUsuario::telefonoE164($datos['telefono'] ?? null, $datos['telefono_pais'] ?? null),
                'email'    => $datos['email'],
            ]);

            // La contraseña solo cambia si se escribió una nueva
            if (! empty($datos['password'])) {
                $usuario->password = $datos['password'];
            }

            $usuario->save();

            $agencia->update([
                'nombre'   => $datos['agencia_nombre'],
                'nit'      => $datos['nit'],
                'telefono' => ValidacionUsuario::telefonoE164($datos['agencia_telefono'] ?? null, $datos['agencia_telefono_pais'] ?? null),
            ]);
        });

        return redirect()
            ->route('admin.agencias.index')
            ->with('exito', "Se guardaron los cambios de {$agencia->nombre}.");
    }

    // Activar o desactivar: agencia y usuario JUNTOS
    public function cambiarEstado(Agencia $agencia)
    {
        $nuevoEstado = ! $agencia->activa;

        DB::transaction(function () use ($agencia, $nuevoEstado) {
            $agencia->update(['activa' => $nuevoEstado]);
            $agencia->usuario()->update(['activo' => $nuevoEstado]);
        });

        $mensaje = $nuevoEstado
            ? "{$agencia->nombre} puede volver a iniciar sesión y reservar."
            : "{$agencia->nombre} ya no puede iniciar sesión.";

        return back()->with('exito', $mensaje);
    }

    // ---------- Validación ----------

    // Limpia los datos de la empresa y del contacto antes de validar
    private function preparar(Request $request): void
    {
        ValidacionUsuario::preparar($request); // persona de contacto

        $request->merge([
            'agencia_nombre'   => ValidacionUsuario::limpiarTexto($request->input('agencia_nombre')),
            'nit'              => preg_replace('/\D/', '', (string) $request->input('nit')) ?: null,
            'agencia_telefono' => preg_replace('/\D/', '', (string) $request->input('agencia_telefono')) ?: null,
        ]);
    }

    // Reglas del contacto (compartidas) + reglas de la empresa
    private function reglas(?Agencia $agencia = null): array
    {
        return array_merge(
            ValidacionUsuario::reglas(
                ignorarId: $agencia?->usuario_id,
                passwordObligatoria: $agencia === null
            ),
            [
                'agencia_nombre' => [
                    'required', 'string', 'min:2', 'max:150',
                    'regex:/^[\pL\pN\s.,&\'-]+$/u',
                    'regex:/\pL/u',
                ],
                'nit' => [
                    'required', 'digits_between:7,12',
                    Rule::unique('agencia', 'nit')->ignore($agencia?->id),
                ],
                'agencia_telefono_pais' => ['nullable', 'required_with:agencia_telefono', Rule::in(array_keys(Paises::lista()))],
                'agencia_telefono'      => ['nullable', 'digits_between:4,15', 'phone:agencia_telefono_pais'],
            ]
        );
    }

    private function mensajes(): array
    {
        return array_merge(ValidacionUsuario::mensajes(), [
            // Contacto: aclarar que es la persona
            'nombre.required'   => 'Escribe el nombre de la persona de contacto.',
            'apellido.required' => 'Escribe el apellido de la persona de contacto.',

            // Empresa
            'agencia_nombre.required' => 'Escribe el nombre de la agencia.',
            'agencia_nombre.min'      => 'El nombre de la agencia debe tener al menos 2 caracteres.',
            'agencia_nombre.max'      => 'El nombre de la agencia es demasiado largo.',
            'agencia_nombre.regex'    => "Usa letras, números y los signos . , & - '",

            'nit.required'       => 'Escribe el NIT de la agencia.',
            'nit.digits_between' => 'El NIT debe tener solo números, entre 7 y 12.',
            'nit.unique'         => 'Ya existe una agencia con este NIT.',

            'agencia_telefono_pais.required_with' => 'Elige el país del teléfono de la agencia.',
            'agencia_telefono_pais.in'            => 'Elige un país de la lista.',
            'agencia_telefono.digits_between'     => 'El teléfono solo puede tener números (entre 4 y 15).',
            'agencia_telefono.phone'              => 'Ese número no es válido para el país elegido.',
        ]);
    }
}