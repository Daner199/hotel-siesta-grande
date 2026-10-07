<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\Request;

/**
 * Clientes: se registran solos desde la página pública.
 * El admin solo los consulta y puede activarlos o desactivarlos.
 */
class ClienteController extends Controller
{
    // Lista con buscador, filtro por estado y paginación
    public function index(Request $request)
    {
        $buscar = trim((string) $request->query('buscar'));
        $estado = $request->query('estado'); // 'activos', 'inactivos' o vacío

        // Escapar % y _ para que se busquen como texto normal
        $texto   = addcslashes($buscar, '%_\\');
        $digitos = preg_replace('/\D/', '', $buscar);

        $clientes = Usuario::query()
            ->where('rol', Usuario::CLIENTE)
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
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        $totales = [
            'todos'   => Usuario::where('rol', Usuario::CLIENTE)->count(),
            'activos' => Usuario::where('rol', Usuario::CLIENTE)->where('activo', true)->count(),
        ];

        return view('admin.clientes.index', compact('clientes', 'buscar', 'estado', 'totales'));
    }

    // Activar o desactivar
    public function cambiarEstado(Usuario $cliente)
    {
        // Evita tocar a un recepcionista, agencia o admin cambiando el id en la URL
        abort_unless($cliente->rol === Usuario::CLIENTE, 404);

        $cliente->activo = ! $cliente->activo;
        $cliente->save();

        $mensaje = $cliente->activo
            ? "{$cliente->nombreCompleto()} puede volver a iniciar sesión."
            : "{$cliente->nombreCompleto()} ya no puede iniciar sesión.";

        return back()->with('exito', $mensaje);
    }
}
