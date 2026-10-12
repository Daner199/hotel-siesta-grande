{{-- Inicio de recepción: quién llega, quién sale y qué falta cobrar --}}
@use('App\Models\EstadoReserva')
@use('App\Support\Estadias')
@use('App\Support\Moneda')

<div class="inicio-rejilla">
    <section class="caja-plana">
        <div class="caja-cabecera">
            <h2>Llegan hoy</h2>
            <a href="{{ route('recepcion.estadias.index', ['pestana' => 'llegadas']) }}">Ver todas</a>
        </div>
        @if ($llegadas->isEmpty())
            <p class="vacio-plano">Nadie más llega hoy.</p>
        @else
            <ul class="lista-trabajo">
                @foreach ($llegadas as $r)
                    <li>
                        <span class="numeros-habitacion">@foreach ($r->habitaciones as $rh)<span>{{ $rh->habitacion->numero }}</span>@endforeach</span>
                        <div>
                            <strong>{{ $r->nombre_titular }}</strong>
                            <span class="dato-gris">{{ $r->codigo }} · {{ $r->cantidad_personas }} {{ $r->cantidad_personas === 1 ? 'persona' : 'personas' }} · hasta el {{ $r->fecha_salida->format('d/m') }}</span>
                        </div>
                        @if ($r->esEstado(EstadoReserva::PENDIENTE))
                            <a href="{{ route('recepcion.reservas.pagar', $r) }}" class="boton-accion">Cobrar {{ Moneda::formato(max(0, $r->adelanto() - $r->totalPagado())) }}</a>
                        @elseif (! Estadias::motivoSinCheckIn($r))
                            <form method="POST" action="{{ route('recepcion.estadias.check-in', $r) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="boton-accion boton-principal">Check-in</button>
                            </form>
                        @else
                            <a href="{{ route('recepcion.estadias.cuenta', $r) }}" class="boton-accion">Cuenta</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="caja-plana">
        <div class="caja-cabecera">
            <h2>Salen hoy</h2>
            <a href="{{ route('recepcion.estadias.index', ['pestana' => 'salidas']) }}">Ver todas</a>
        </div>
        @if ($salidas->isEmpty())
            <p class="vacio-plano">Nadie más sale hoy.</p>
        @else
            <ul class="lista-trabajo">
                @foreach ($salidas as $r)
                    @php $saldo = $r->saldo(); @endphp
                    <li>
                        <span class="numeros-habitacion">@foreach ($r->habitaciones as $rh)<span>{{ $rh->habitacion->numero }}</span>@endforeach</span>
                        <div>
                            <strong>{{ $r->nombre_titular }}</strong>
                            <span @class(['dato-gris', 'dato-alerta' => $saldo > 0])>{{ $saldo > 0 ? 'Saldo ' . Moneda::formato($saldo) : 'Cuenta pagada' }}</span>
                        </div>
                        <a href="{{ route('recepcion.estadias.cuenta', $r) }}" @class(['boton-accion', 'boton-principal' => $saldo == 0])>
                            {{ $saldo > 0 ? 'Cobrar' : 'Check-out' }}
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>

<section class="caja-plana caja-inicio">
    <div class="caja-cabecera">
        <h2>Pendientes de pago</h2>
        <a href="{{ route('recepcion.reservas.index') }}">Reservas</a>
    </div>
    @if ($pendientes->isEmpty())
        <p class="vacio-plano">No hay reservas sin adelanto.</p>
    @else
        <div class="tabla-envoltura">
            <table class="tabla tabla-compacta tabla-inicio">
                <thead>
                    <tr>
                        <th>Llegada</th>
                        <th>Titular</th>
                        <th class="num">Falta del adelanto</th>
                        <th><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pendientes as $r)
                        <tr>
                            <td>{{ $r->fecha_entrada->format('d/m/Y') }}</td>
                            <td><strong>{{ $r->nombre_titular }}</strong> <span class="dato-gris">{{ $r->codigo }}</span></td>
                            <td class="num">{{ Moneda::formato(max(0, $r->adelanto() - $r->totalPagado())) }}</td>
                            <td><div class="acciones"><a href="{{ route('recepcion.reservas.pagar', $r) }}" class="boton-accion">Cobrar</a></div></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
