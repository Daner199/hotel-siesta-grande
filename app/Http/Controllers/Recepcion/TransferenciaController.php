<?php

namespace App\Http\Controllers\Recepcion;

use App\Http\Controllers\Controller;
use App\Models\Pago;
use App\Support\Moneda;
use App\Support\Pagos;
use App\Support\ValidacionUsuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Transferencias por revisar: recepción mira el comprobante y aprueba o rechaza.
 * Al aprobar se confirma la reserva (si completa el adelanto), se emite el recibo y,
 * como confirma un recepcionista, se genera su comisión.
 */
class TransferenciaController extends Controller
{
    public const MOTIVOS = [
        'El monto del comprobante no coincide.',
        'El comprobante no se lee bien.',
        'El dinero no llegó a la cuenta del hotel.',
        'El número de operación no coincide.',
    ];

    public function index(Request $request)
    {
        $estado = in_array($request->query('estado'), [Pago::APROBADO, Pago::RECHAZADO], true)
            ? $request->query('estado') : Pago::PENDIENTE;

        $pagos = Pago::whereHas('transferencia')
            ->where('estado', $estado)
            ->with(['reserva', 'transferencia.revisor', 'registradoPor'])
            ->orderBy($estado === Pago::PENDIENTE ? 'fecha_pago' : 'id', $estado === Pago::PENDIENTE ? 'asc' : 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('recepcion.transferencias.index', [
            'pagos'      => $pagos,
            'estado'     => $estado,
            'pendientes' => Pago::whereHas('transferencia')->where('estado', Pago::PENDIENTE)->count(),
        ]);
    }

    public function show(Pago $pago)
    {
        abort_unless($pago->transferencia, 404);
        $pago->load(['reserva.habitaciones.habitacion', 'reserva.estado', 'reserva.agencia', 'transferencia.revisor', 'registradoPor']);

        return view('recepcion.transferencias.ver', [
            'pago'     => $pago,
            'reserva'  => $pago->reserva,
            'esPdf'    => $pago->transferencia->esPdf(),
            'existe'   => Storage::disk('local')->exists($pago->transferencia->comprobante),
            'motivos'  => self::MOTIVOS,
        ]);
    }

    public function aprobar(Request $request, Pago $pago)
    {
        abort_unless($pago->transferencia, 404);

        try {
            $pago = Pagos::aprobarTransferencia($pago, $request->user());
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()->route('recepcion.recibos.show', $pago->recibo)
            ->with('exito', 'Transferencia aprobada: ' . Moneda::formato($pago->monto) . '.');
    }

    public function rechazar(Request $request, Pago $pago)
    {
        abort_unless($pago->transferencia, 404);

        $request->merge(['motivo_rechazo' => ValidacionUsuario::limpiarTexto($request->input('motivo_rechazo'))]);
        $datos = $request->validate(
            ['motivo_rechazo' => ['required', 'string', 'max:255']],
            ['motivo_rechazo.required' => 'Elige o escribe el motivo: el cliente lo verá.', 'motivo_rechazo.max' => 'El motivo es demasiado largo.'],
        );

        try {
            Pagos::rechazarTransferencia($pago, $request->user(), $datos['motivo_rechazo']);
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()->route('recepcion.transferencias.index')
            ->with('exito', 'Transferencia rechazada. El cliente verá el motivo en su reserva.');
    }
}
