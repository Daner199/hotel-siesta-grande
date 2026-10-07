<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;


class LoginController extends Controller
{
    // Muestra el formulario
    public function mostrar()
    {
        return view('auth.login');
    }

    // Procesa el formulario
        // Procesa el formulario
    public function ingresar(Request $request)
    {
        $datos = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required'    => 'Escribe tu correo.',
            'email.email'       => 'El correo no tiene un formato válido.',
            'password.required' => 'Escribe tu contraseña.',
        ]);

        // 1. Buscar el usuario por correo
        $usuario = Usuario::where('email', Str::lower(trim($datos['email'])))->first();

        // 2. Correo inexistente o contraseña incorrecta: mismo mensaje
        if (! $usuario || ! Hash::check($datos['password'], $usuario->password)) {
            return back()
                ->withErrors(['email' => 'El correo o la contraseña no son correctos.'])
                ->onlyInput('email');
        }

        // 3. Contraseña correcta, pero la cuenta está desactivada
        if (! $usuario->activo) {
            return back()
                ->withErrors(['email' => 'Tu cuenta está desactivada. Comunícate con el hotel.'])
                ->onlyInput('email');
        }

        // 4. Cuenta de agencia: la agencia también debe estar activa
        if ($usuario->rol === Usuario::AGENCIA && ! $usuario->agencia?->activa) {
            return back()
                ->withErrors(['email' => 'La agencia de esta cuenta está desactivada. Comunícate con el hotel.'])
                ->onlyInput('email');
        }

        // 5. Todo correcto: iniciar sesión
        Auth::login($usuario, $request->boolean('recordar'));
        $request->session()->regenerate();

        return redirect()->to($usuario->rutaInicio());
    }

    // Cierra sesión
    public function salir(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

                return redirect()->route('inicio');
    }
}