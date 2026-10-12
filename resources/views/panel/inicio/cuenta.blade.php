{{-- Inicio del cliente y de la agencia: la próxima estadía, sus reservas activas y lo que falta pagar --}}
@use('App\Models\EstadoReserva')
@use('App\Models\Hotel')
@use('App\Support\Moneda')
@use('App\Support\Reservas')

@if (! $proxima)
    <section class="caja-plana caja-inicio">
        <p class="vacio-plano">No tienes reservas activas.</p>
    </section>
@else
    @php
        $hotel = Hotel::datos();
        $saldo = $proxima->saldo();
        $faltaAdelanto = $proxima->esEstado(EstadoReserva::PENDIENTE) ? max(0, $proxima->adelanto() - $proxima->totalPagado()) : 0;
    @endphp

    <section class="caja-plana caja-inicio proxima-estadia">
        <div class="caja-cabecera">
            <h2>{{ $prefijo === 'agencia' ? 'Próxima llegada' : 'Tu próxima estadía' }}</h2>
            <span class="estado estado-reserva estado-reserva-{{ strtolower($proxima->estado->nombre) }}">{{ $proxima->estado->etiqueta() }}</span>
        </div>

        <div class="proxima-datos">
            <div>
                <p class="proxima-fechas">
                    {{ ucfirst($proxima->fecha_entrada->locale('es')->isoFormat('ddd D [de] MMM')) }}
                    <i data-lucide="arrow-right"></i>
                    {{ ucfirst($proxima->fecha_salida->locale('es')->isoFormat('ddd D [de] MMM')) }}
                </p>
                <p class="dato-gris">
                    {{ $proxima->codigo }}
                    @if ($prefijo === 'agencia') · {{ $proxima->nombre_titular }} @endif
                    · {{ implode(' · ', Reservas::lineasEstadia($proxima)) }}
                </p>
                <p class="dato-gris">Check-in desde las {{ $hotel['check_in'] }} · check-out hasta las {{ $hotel['check_out'] }}</p>
            </div>

            <dl class="lista-datos proxima-montos">
                <div><dt>Total</dt><dd>{{ Moneda::formato($proxima->total() + $proxima->totalConsumos()) }}</dd></div>
                <div><dt>Pagado</dt><dd>{{ Moneda::formato($proxima->totalPagado()) }}</dd></div>
                <div class="dato-grande"><dt>{{ $faltaAdelanto > 0 ? 'Adelanto pendiente' : 'Saldo' }}</dt><dd>{{ Moneda::formato($faltaAdelanto > 0 ? $faltaAdelanto : $saldo) }}</dd></div>
            </dl>
        </div>

        <div class="cabecera-acciones">
            @if ($saldo > 0 && $proxima->sePuedePagar())
                <a href="{{ route($prefijo . '.reservas.pagar', $proxima) }}" class="btn btn-primario">Pagar</a>
            @endif
            <a href="{{ route($prefijo . '.reservas.show', $proxima) }}" class="btn btn-secundario">Ver reserva</a>
        </div>
    </section>

    @if ($activas->count() > 1)
        <section class="caja-plana caja-inicio">
            <div class="caja-cabecera">
                <h2>Reservas activas</h2>
                <a href="{{ route($prefijo . '.reservas.index') }}">Ver todas</a>
            </div>
            <div class="tabla-envoltura">
                <table class="tabla tabla-compacta tabla-inicio">
                    <thead>
                        <tr>
                            <th>Fechas</th>
                            <th>{{ $prefijo === 'agencia' ? 'Titular' : 'Reserva' }}</th>
                            <th>Estado</th>
                            <th class="num">Saldo</th>
                            <th><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($activas as $r)
                            @php $s = $r->saldo(); @endphp
                            <tr>
                                <td>{{ $r->fecha_entrada->format('d/m') }} – {{ $r->fecha_salida->format('d/m/Y') }}</td>
                                <td>
                                    <strong>{{ $prefijo === 'agencia' ? $r->nombre_titular : $r->codigo }}</strong>
                                    @if ($prefijo === 'agencia') <span class="dato-gris">{{ $r->codigo }}</span> @endif
                                </td>
                                <td><span class="estado estado-reserva estado-reserva-{{ strtolower($r->estado->nombre) }}">{{ $r->estado->etiqueta() }}</span></td>
                                <td @class(['num', 'dato-alerta' => $s > 0])>{{ Moneda::formato($s) }}</td>
                                <td>
                                    <div class="acciones">
                                        @if ($s > 0 && $r->sePuedePagar())
                                            <a href="{{ route($prefijo . '.reservas.pagar', $r) }}" class="boton-accion">Pagar</a>
                                        @endif
                                        <a href="{{ route($prefijo . '.reservas.show', $r) }}" class="boton-accion">Ver</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endif
