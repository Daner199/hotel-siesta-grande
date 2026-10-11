{{-- Tabla de pagos. Variables: $pagos, $conReserva (mostrar la columna Reserva), $prefijo --}}
@use('App\Support\Moneda')

<div class="tabla-envoltura">
    <table class="tabla tabla-compacta">
        <thead>
            <tr>
                <th>Fecha</th>
                @if ($conReserva ?? false) <th>Reserva</th> @endif
                <th>Método</th>
                <th>Monto</th>
                <th>Estado</th>
                <th>Nro. de transacción</th>
                <th><span class="sr-only">Recibo</span></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($pagos as $pago)
                <tr @class(['fila-inactiva' => $pago->estado !== 'APROBADO'])>
                    <td>
                        {{ $pago->fecha_pago->format('d/m/Y H:i') }}
                        @if ($rol === 'RECEPCIONISTA' && $pago->registradoPor)
                            <span class="dato-secundario">{{ $pago->registradoPor->nombreCompleto() }}</span>
                        @endif
                    </td>
                    @if ($conReserva ?? false)
                        <td>
                            <a href="{{ route($prefijo . '.show', $pago->reserva_id) }}" class="enlace-reserva">{{ $pago->reserva->codigo }}</a>
                            <span class="dato-secundario">{{ $pago->reserva->nombre_titular }}</span>
                        </td>
                    @endif
                    <td>
                        <span class="dato-icono"><i data-lucide="{{ $pago->metodo->icono() }}"></i> {{ $pago->metodo->etiqueta() }}</span>
                        @if ($pago->observacion)
                            <span class="dato-secundario">{{ $pago->observacion }}</span>
                        @endif
                        @if ($pago->transferencia)
                            <span class="dato-secundario">
                                Nro. de operación {{ $pago->transferencia->numero_operacion }} ·
                                <a href="{{ route($rutaComprobante, $pago) }}" target="_blank" rel="noopener">ver comprobante</a>
                            </span>
                        @endif
                    </td>
                    <td>
                        <span @class(['precio', 'precio-anulado' => $pago->estado !== 'APROBADO'])>{{ Moneda::formato($pago->monto) }}</span>
                    </td>
                    <td><span class="estado estado-pago-{{ strtolower($pago->estado) }}">{{ $pago->etiquetaEstado() }}</span></td>
                    <td><code class="referencia">{{ $pago->referencia ?? '—' }}</code></td>
                    <td>
                        @if ($pago->recibo)
                            <a href="{{ route($rutaRecibo . '.show', $pago->recibo) }}" class="boton-accion">
                                <i data-lucide="file-text"></i>
                                <span>{{ $pago->recibo->codigo() }}</span>
                            </a>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
