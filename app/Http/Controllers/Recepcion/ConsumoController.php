<?php

namespace App\Http\Controllers\Recepcion;

use App\Http\Controllers\Controller;
use App\Models\Consumo;
use App\Models\EstadoReserva;
use App\Models\Reserva;
use App\Models\TipoConsumo;
use App\Support\Moneda;
use App\Support\ValidacionUsuario;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Consumos del restaurante y del frigobar cargados a la cuenta de un huésped alojado.
 * Se pagan en el check-out. Un consumo mal cargado se puede anular mientras el huésped siga alojado.
 */
class ConsumoController extends Controller
{
    public function index(Request $request)
    {
        $buscar = trim((string) $request->query('buscar'));
        $tipo   = $request->integer('tipo') ?: null;
        $texto  = '%' . addcslashes($buscar, '%_\\') . '%';

        $consulta = Consumo::query()
            ->when($tipo, fn ($q) => $q->where('tipo_consumo_id', $tipo))
            ->when($buscar !== '', fn ($q) => $q->where(fn ($w) => $w
                ->whereRaw('unaccent(descripcion) ILIKE unaccent(?)', [$texto])
                ->orWhereHas('reserva', fn ($r) => $r->where('codigo', 'ILIKE', $texto)
                    ->orWhereRaw('unaccent(nombre_titular) ILIKE unaccent(?)', [$texto]))));

        $total = (clone $consulta)->sum(\Illuminate\Support\Facades\DB::raw('cantidad * precio_unitario'));

        return view('recepcion.consumos.index', [
            'consumos' => $consulta->with(['reserva.estado', 'reserva.habitaciones.habitacion', 'tipo', 'registradoPor'])
                ->orderByDesc('fecha_consumo')->orderByDesc('id')->paginate(20)->withQueryString(),
            'tipos'    => TipoConsumo::orderBy('id')->get(),
            'total'    => (float) $total,
            'buscar'   => $buscar,
            'tipo'     => $tipo,
        ]);
    }

    public function store(Request $request, Reserva $reserva)
    {
        $volver = redirect()->to(route('recepcion.estadias.cuenta', $reserva) . '#consumos');

        if (! $reserva->esEstado(EstadoReserva::CHECK_IN)) {
            return $volver->with('error', 'Solo se cargan consumos a huéspedes alojados.');
        }

        $descripcion = ValidacionUsuario::limpiarTexto($request->input('descripcion'));
        $request->merge([
            'descripcion'     => $descripcion ? mb_strtoupper(mb_substr($descripcion, 0, 1)) . mb_substr($descripcion, 1) : null,
            'precio_unitario' => Moneda::leer($request->input('precio_unitario')),
        ]);

        $datos = $request->validate([
            'tipo_consumo_id' => ['required', Rule::exists('tipo_consumo', 'id')],
            'descripcion'     => ['bail', 'required', 'string', 'min:2', 'max:200'],
            'cantidad'        => ['bail', 'required', 'integer', 'between:1,99'],
            'precio_unitario' => ['bail', 'required', 'numeric', 'decimal:0,2', 'gt:0', 'max:99999.99'],
        ], [
            'tipo_consumo_id.*'        => 'Elige restaurante o frigobar.',
            'descripcion.required'     => 'Escribe qué consumió.',
            'descripcion.min'          => 'Escribe qué consumió.',
            'descripcion.max'          => 'El detalle es demasiado largo.',
            'cantidad.*'               => 'La cantidad debe ser de 1 a 99.',
            'precio_unitario.required' => 'Escribe el precio.',
            'precio_unitario.numeric'  => 'Escribe un precio válido, por ejemplo 12,50.',
            'precio_unitario.decimal'  => 'El precio puede tener como máximo 2 decimales.',
            'precio_unitario.gt'       => 'El precio debe ser mayor que 0.',
            'precio_unitario.max'      => 'El precio es demasiado alto.',
        ]);

        try {
            $consumo = Consumo::create($datos + [
                'reserva_id'                => $reserva->id,
                'registrado_por_usuario_id' => $request->user()->id,
            ]);
        } catch (QueryException $e) {
            // El trigger de la BD: la reserva dejó de estar alojada mientras se cargaba
            if ($e->getCode() === '23514') {
                return $volver->with('error', 'Solo se cargan consumos a huéspedes alojados.');
            }
            throw $e;
        }

        return $volver->with('exito', "Cargado: {$consumo->descripcion} × {$consumo->cantidad} (" . Moneda::formato($consumo->importe()) . ').');
    }

    public function destroy(Consumo $consumo)
    {
        $reserva = $consumo->reserva()->with('estado')->first();
        $volver  = redirect()->to(route('recepcion.estadias.cuenta', $reserva) . '#consumos');

        if (! $reserva->esEstado(EstadoReserva::CHECK_IN)) {
            return $volver->with('error', 'Después del check-out los consumos ya no se anulan.');
        }

        $consumo->delete();

        return $volver->with('exito', "Anulado: {$consumo->descripcion} (" . Moneda::formato($consumo->importe()) . ').');
    }
}
