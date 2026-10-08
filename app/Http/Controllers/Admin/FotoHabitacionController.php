<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FotoHabitacion;
use App\Models\Habitacion;
use App\Support\Galeria;
use Illuminate\Http\Request;

/**
 * Fotos propias de una habitación (opcionales). Se manejan en la pantalla de editar habitación.
 * Si una habitación no tiene fotos propias, se muestran las de su tipo.
 */
class FotoHabitacionController extends Controller
{
    public function store(Request $request, Habitacion $habitacion)
    {
        $archivos = Galeria::validar($request, $habitacion->fotos());
        $n = Galeria::subir($habitacion->fotos(), $archivos, $habitacion->carpetaFotos());

        return back()->with('exito', $n === 1 ? 'Se subió 1 foto.' : "Se subieron {$n} fotos.");
    }

    public function principal(Habitacion $habitacion, FotoHabitacion $foto)
    {
        Galeria::hacerPrincipal($habitacion->fotos(), $foto);

        return back()->with('exito', "Esa foto ahora es la principal de la habitación {$habitacion->numero}.");
    }

    public function mover(Request $request, Habitacion $habitacion, FotoHabitacion $foto)
    {
        $direccion = $request->validate(['direccion' => ['required', 'in:izquierda,derecha']])['direccion'];
        Galeria::mover($habitacion->fotos(), $foto, $direccion);

        return back();
    }

    public function destroy(Habitacion $habitacion, FotoHabitacion $foto)
    {
        Galeria::eliminar($habitacion->fotos(), $foto);

        return back()->with('exito', 'Se eliminó la foto.');
    }
}
