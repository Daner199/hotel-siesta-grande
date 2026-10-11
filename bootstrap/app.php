<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Nombre corto para usar en las rutas: 'rol:ADMINISTRADOR'
        $middleware->alias([
            'rol' => \App\Http\Middleware\VerificarRol::class,
        ]);

        // Sin sesión y entra a una página protegida → al login
        $middleware->redirectGuestsTo(fn () => route('login'));

        // Con sesión y entra al login → a su panel
        $middleware->redirectUsersTo(fn (Request $request) => $request->user()->rutaInicio());
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Si un pago no pasa la validación, el número de tarjeta y el CVV NO vuelven al formulario
        $exceptions->dontFlash(['tarjeta_numero', 'tarjeta_vence', 'tarjeta_cvv']);
    })->create();