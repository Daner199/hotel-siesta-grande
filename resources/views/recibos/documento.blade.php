{{--
    Recibo de pago. La misma vista sirve para la pantalla y para el PDF (dompdf),
    por eso usa tablas y CSS simple (dompdf no entiende flex ni grid).
    Variables: $recibo (con pago.reserva, pago.metodo, pago.transferencia, pago.registradoPor),
               $hotel (Hotel::datos()), $logo (data URI o null), $qr (data URI del QR de verificación)
--}}
@use('App\Models\MetodoPago')
@use('App\Models\Pago')
@use('App\Support\Moneda')
@use('App\Support\Paises')

@php
    $pago    = $recibo->pago;
    $reserva = $pago->reserva;
    $total   = $reserva->total();
    $pagadoEntonces = max(0, $total - (float) $recibo->saldo_pendiente);

    $pagador = $reserva->agencia
        ? $reserva->agencia->nombre . ' (NIT ' . $reserva->agencia->nit . ')'
        : ($reserva->cliente?->nombreCompleto() ?? $reserva->nombre_titular);

    $metodo = match ($pago->metodo->nombre) {
        MetodoPago::TARJETA       => 'Tarjeta' . (preg_match('/(\d{4})/', (string) $pago->observacion, $m) ? " terminada en {$m[1]}" : ''),
        MetodoPago::TRANSFERENCIA => 'Transferencia bancaria',
        MetodoPago::QR            => 'Pago con QR',
        default                   => $pago->metodo->etiqueta(),
    };

    $direccion = trim($hotel['direccion']['calle'] . ', ' . $hotel['direccion']['ciudad'], ', ');
@endphp

<div class="recibo">
    <style>
        .recibo { font-size: 12px; line-height: 1.45; color: #17221C; }
        .recibo table { width: 100%; border-collapse: collapse; }
        .recibo td { vertical-align: top; padding: 0; }
        .recibo .cabecera td { padding-bottom: 14px; border-bottom: 2px solid #1D3A2E; }
        .recibo .logo { width: 52px; height: 52px; margin-right: 10px; }
        .recibo .hotel-nombre { font-size: 17px; font-weight: bold; color: #1D3A2E; }
        .recibo .gris { color: #5D6A63; }
        .recibo .chico { font-size: 10.5px; }
        .recibo .titulo { font-size: 15px; font-weight: bold; text-align: right; }
        .recibo .numero { font-size: 13px; text-align: right; }
        .recibo .datos { margin-top: 12px; }
        .recibo .datos td { padding: 6px 0; border-bottom: 1px solid #E3E6E1; }
        .recibo .datos td.etiqueta { width: 34%; color: #5D6A63; }
        .recibo .monto { margin-top: 14px; }
        .recibo .monto td { padding: 10px 12px; background: #F2F3EE; }
        .recibo .monto .valor { font-size: 20px; font-weight: bold; text-align: right; white-space: nowrap; }
        .recibo .cuenta { margin-top: 12px; }
        .recibo .cuenta td { padding: 4px 0; }
        .recibo .cuenta td.num { text-align: right; white-space: nowrap; }
        .recibo .cuenta tr.saldo td { padding-top: 7px; border-top: 1px solid #1D3A2E; font-weight: bold; }
        .recibo .pie { margin-top: 18px; }
        .recibo .pie td { vertical-align: middle; }
        .recibo .qr { width: 92px; height: 92px; }
        .recibo .sello { display: inline-block; margin-top: 10px; padding: 3px 8px; border: 1.5px solid #A63D64;
                         color: #A63D64; font-weight: bold; }
    </style>

    {{-- Hotel y número --}}
    <table class="cabecera">
        <tr>
            <td>
                <table>
                    <tr>
                        @if ($logo)
                            <td style="width: 62px;"><img src="{{ $logo }}" class="logo" alt=""></td>
                        @endif
                        <td>
                            <div class="hotel-nombre">{{ $hotel['nombre'] }}</div>
                            <div class="gris chico">{{ $direccion }} · {{ $hotel['direccion']['departamento'] }}, {{ $hotel['direccion']['pais'] }}</div>
                            <div class="gris chico">
                                @if ($hotel['telefono']) Tel. {{ Paises::formatearTelefono($hotel['telefono']) }} @endif
                                @if ($hotel['correo']) · {{ $hotel['correo'] }} @endif
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 38%;">
                <div class="titulo">Recibo de pago</div>
                <div class="numero">Nro. {{ $recibo->codigo() }}</div>
                <div class="numero gris chico">{{ $recibo->fecha_emision->format('d/m/Y H:i') }}</div>
            </td>
        </tr>
    </table>

    {{-- Datos del pago --}}
    <table class="datos">
        <tr><td class="etiqueta">Recibí de</td><td>{{ $pagador }}</td></tr>
        @if ($reserva->agencia || $reserva->nombre_titular !== $pagador)
            <tr><td class="etiqueta">Titular de la reserva</td><td>{{ $reserva->nombre_titular }}</td></tr>
        @endif
        <tr>
            <td class="etiqueta">Concepto</td>
            <td>
                Pago a cuenta de la reserva {{ $reserva->codigo }}<br>
                <span class="gris chico">
                    Del {{ $reserva->fecha_entrada->format('d/m/Y') }} al {{ $reserva->fecha_salida->format('d/m/Y') }}
                    · {{ $reserva->noches() }} {{ $reserva->noches() === 1 ? 'noche' : 'noches' }}
                    · hab. {{ $reserva->habitaciones->map(fn ($rh) => $rh->habitacion->numero)->join(', ') }}
                </span>
            </td>
        </tr>
        <tr><td class="etiqueta">Forma de pago</td><td>{{ $metodo }}</td></tr>
        @if ($pago->transferencia)
            <tr><td class="etiqueta">Nro. de operación</td><td>{{ $pago->transferencia->numero_operacion }}</td></tr>
        @endif
        <tr><td class="etiqueta">Nro. de transacción</td><td>{{ $pago->referencia }}</td></tr>
        <tr><td class="etiqueta">Fecha del pago</td><td>{{ $pago->fecha_pago->format('d/m/Y H:i:s') }}</td></tr>
        @if ($pago->registradoPor && $pago->registradoPor->rol === 'RECEPCIONISTA')
            <tr><td class="etiqueta">Atendido por</td><td>{{ $pago->registradoPor->nombreCompleto() }}</td></tr>
        @endif
    </table>

    {{-- Monto --}}
    <table class="monto">
        <tr>
            <td>
                <div class="gris chico">Monto recibido</div>
                <div>Son: {{ Moneda::enLetras($pago->monto) }}</div>
            </td>
            <td class="valor">{{ Moneda::formato($pago->monto) }}</td>
        </tr>
    </table>

    {{-- Estado de cuenta al momento del recibo --}}
    <table class="cuenta">
        <tr><td class="gris">Total de la estadía</td><td class="num">{{ Moneda::formato($total) }}</td></tr>
        <tr><td class="gris">Pagado a la fecha</td><td class="num">{{ Moneda::formato($pagadoEntonces) }}</td></tr>
        <tr class="saldo"><td>Saldo pendiente</td><td class="num">{{ Moneda::formato($recibo->saldo_pendiente) }}</td></tr>
    </table>

    @if ($pago->estado === Pago::DEVUELTO)
        <div class="sello">Pago devuelto por cancelación de la reserva</div>
    @endif

    {{-- Verificación --}}
    <table class="pie">
        <tr>
            <td style="width: 104px;"><img src="{{ $qr }}" class="qr" alt="QR de verificación"></td>
            <td>
                <div class="chico">Para verificar este recibo escanea el código o entra a:</div>
                <div class="chico gris" style="word-break: break-all;">{{ $recibo->urlVerificacion() }}</div>
                <div class="chico" style="margin-top: 8px;"><strong>Este documento no es una factura.</strong></div>
            </td>
        </tr>
    </table>
</div>
