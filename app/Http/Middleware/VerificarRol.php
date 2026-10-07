<?php

namespace App\Http\Middleware;

use App\Models\Usuario;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * 1. Si la cuenta fue desactivada mientras tenía sesión abierta → la saca.
 * 2. Revisa que el usuario tenga uno de los roles permitidos.
 *
 * Uso en rutas: ->middleware('rol:ADMINISTRADOR')
 */
class VerificarRol
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        /** @var Usuario|null $usuario */
        $usuario = $request->user();

        if ($usuario && ! $this->cuentaActiva($usuario)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Tu cuenta fue desactivada. Comunícate con el hotel.']);
        }

        if (! $usuario || ! in_array($usuario->rol, $roles, true)) {
            abort(403, 'No tienes permiso para entrar a esta sección.');
        }

        return $next($request);
    }

    private function cuentaActiva(Usuario $usuario): bool
    {
        if (! $usuario->activo) {
            return false;
        }

        if ($usuario->rol === Usuario::AGENCIA) {
            return (bool) $usuario->agencia?->activa;
        }

        return true;
    }
}