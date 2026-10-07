<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;


class LoginController extends Controller
{
    // Muestra el formulario
    public function mostrar()
    {
        return view('auth.login');
    }

    // Fallos permitidos y segundos de bloqueo al superarlos
    private const MAX_INTENTOS = 5;
    private const BLOQUEO_SEGUNDOS = 60;

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

        $email = Str::lower(trim($datos['email']));

        // 0. Demasiados fallos con este correo desde esta IP: esperar
        $clave = 'login|' . $email . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($clave, self::MAX_INTENTOS)) {
            $segundos = RateLimiter::availableIn($clave);

            return back()
                ->withErrors(['email' => "Demasiados intentos. Espera {$segundos} segundos e inténtalo de nuevo."])
                ->onlyInput('email');
        }

        // 1. Buscar el usuario por correo
        $usuario = Usuario::where('email', $email)->first();

        // 2. Correo inexistente o contraseña incorrecta: mismo mensaje (y cuenta un fallo)
        if (! $usuario || ! Hash::check($datos['password'], $usuario->password)) {
            RateLimiter::hit($clave, self::BLOQUEO_SEGUNDOS);

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

        // 5. Todo correcto: borrar los fallos e iniciar sesión
        RateLimiter::clear($clave);
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