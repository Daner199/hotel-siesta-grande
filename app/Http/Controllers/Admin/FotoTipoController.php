<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FotoTipoHabitacion;
use App\Models\TipoHabitacion;
use App\Support\Galeria;
use Illuminate\Http\Request;

/**
 * Galería de fotos de un tipo de habitación (pestaña "Fotos" junto a "Tarifas").
 * La lógica está en App\Support\Galeria (compartida con las fotos de habitaciones).
 */
class FotoTipoController extends Controller
{
    public function index(TipoHabitacion $tipo)
    {
        return view('admin.tipos.fotos', [
            'tipo'  => $tipo,
            'fotos' => $tipo->fotos()->get(),
        ]);
    }

    public function store(Request $request, TipoHabitacion $tipo)
    {
        $archivos = Galeria::validar($request, $tipo->fotos());
        $n = Galeria::subir($tipo->fotos(), $archivos, $tipo->carpetaFotos());

        return back()->with('exito', $n === 1 ? 'Se subió 1 foto.' : "Se subieron {$n} fotos.");
    }

    public function principal(TipoHabitacion $tipo, FotoTipoHabitacion $foto)
    {
        Galeria::hacerPrincipal($tipo->fotos(), $foto);

        return back()->with('exito', "Esa foto ahora es la principal de {$tipo->nombre}.");
    }

    public function mover(Request $request, TipoHabitacion $tipo, FotoTipoHabitacion $foto)
    {
        $direccion = $request->validate(['direccion' => ['required', 'in:izquierda,derecha']])['direccion'];
        Galeria::mover($tipo->fotos(), $foto, $direccion);

        return back();
    }

    public function destroy(TipoHabitacion $tipo, FotoTipoHabitacion $foto)
    {
        Galeria::eliminar($tipo->fotos(), $foto);

        return back()->with('exito', 'Se eliminó la foto.');
    }
}
