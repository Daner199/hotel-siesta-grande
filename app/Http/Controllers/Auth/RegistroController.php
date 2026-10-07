<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Support\Paises;
use App\Support\ValidacionUsuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Registro público: SOLO crea cuentas de CLIENTE.
 * Las reglas están en App\Support\ValidacionUsuario (compartidas).
 */
class RegistroController extends Controller
{
    // Muestra el formulario con la lista de países
    public function mostrar()
    {
        return view('auth.registro', [
            'paises' => Paises::lista(),
        ]);
    }

    // Procesa el formulario
    public function registrar(Request $request)
    {
        // 1. Limpiar los datos (espacios, correo en minúsculas, teléfono solo números)
        ValidacionUsuario::preparar($request);

        // 2. Validar con las reglas compartidas.
        //    Los mensajes del registro le hablan directo al cliente ("tu").
        $datos = $request->validate(
            ValidacionUsuario::reglas(),
            array_merge(ValidacionUsuario::mensajes(), [
                'nombre.required'             => 'Escribe tu nombre.',
                'apellido.required'           => 'Escribe tu apellido.',
                'telefono_pais.required_with' => 'Elige el país de tu teléfono.',
                'email.required'              => 'Escribe tu correo.',
                'email.unique'                => 'Ya existe una cuenta con este correo. Inicia sesión.',
            ])
        );

        // 3. Guardar. El rol se fija aquí, nunca viene del formulario.
        $usuario = Usuario::create([
            'nombre'   => $datos['nombre'],
            'apellido' => $datos['apellido'],
            'telefono' => ValidacionUsuario::telefonoE164($datos['telefono'] ?? null, $datos['telefono_pais'] ?? null),
            'email'    => $datos['email'],
            'password' => $datos['password'],
            'rol'      => Usuario::CLIENTE,
            'activo'   => true,
        ]);

        // 4. Iniciar sesión directamente
        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect()->route('cliente.inicio');
    }
}