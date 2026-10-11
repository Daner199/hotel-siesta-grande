@extends('layouts.panel')

{{-- Detalle de una reserva (lo comparten cliente, agencia y recepción) --}}
@use('App\Models\EstadoReserva')
@use('App\Models\Reserva')
@use('App\Support\Disponibilidad')
@use('App\Support\Moneda')

@php
    $hotel     = \App\Models\Hotel::datos();
    $listado   = $rol === 'RECEPCIONISTA' ? 'Reservas' : ($rol === 'AGENCIA' ? 'Reservas de la agencia' : 'Mis reservas');
    $noches    = $reserva->noches();
    $total     = $reserva->total();
    $adelanto  = $reserva->adelanto();
    $pagado    = $reserva->totalPagado();
    $estado    = $reserva->estado->nombre;
    $cancelable = $reserva->sePuedeCancelar();
    $devuelve  = $reserva->devuelveSiCancela();
    $limite    = $reserva->limiteCancelacion();
    $editable  = $rol === 'RECEPCIONISTA' && $cancelable;

    $etiquetas = [];
    if ($reserva->agencia_id) {
        $etiquetas[] = 'Agencia: ' . Disponibilidad::DESCUENTO_AGENCIA . ' % menos';
    }
    if ($noches > Disponibilidad::NOCHES_PRECIO_NORMAL) {
        $etiquetas[] = 'Estadía larga: ' . Disponibilidad::DESCUENTO_LARGA . ' % menos desde la 8.ª noche';
    }

    $pregunta = $devuelve
        ? "¿Cancelar la reserva {$reserva->codigo}? Se devuelve todo lo pagado."
        : "¿Cancelar la reserva {$reserva->codigo}? Faltan menos de " . Reserva::HORAS_CANCELACION . ' h para el check-in: lo pagado NO se devuelve.';
@endphp

@section('titulo', "Reserva {$reserva->codigo}")

@section('panel')
<header class="cabecera-pagina">
    <div>
        <nav class="migas" aria-label="Ruta">
            <a href="{{ route($inicio) }}">Inicio</a>
            <i data-lucide="chevron-right"></i>
            <a href="{{ route($prefijo . '.index') }}">{{ $listado }}</a>
            <i data-lucide="chevron-right"></i>
            <span>{{ $reserva->codigo }}</span>
        </nav>
        <h1>Reserva {{ $reserva->codigo }}</h1>
        <p class="cabecera-estado">
            <span class="estado estado-reserva estado-reserva-{{ strtolower($estado) }}">{{ $reserva->estado->etiqueta() }}</span>
            Hecha el {{ $reserva->fecha_creacion->format('d/m/Y \a \l\a\s H:i') }}
            @if ($rol === 'RECEPCIONISTA') por {{ $reserva->creador->nombreCompleto() }} @endif
        </p>
    </div>

    <div class="cabecera-acciones">
        @if ($editable)
            <a href="{{ route($prefijo . '.edit', $reserva) }}" class="btn btn-secundario">
                <i data-lucide="pencil"></i>
                Editar datos
            </a>
        @endif
        @if ($cancelable)
            <form method="POST" action="{{ route($prefijo . '.cancelar', $reserva) }}" data-confirmar="{{ $pregunta }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-secundario boton-cancelar">
                    <i data-lucide="calendar-x"></i>
                    Cancelar reserva
                </button>
            </form>
        @endif
    </div>
</header>

<div class="reserva-detalle">
    <div class="reserva-detalle-datos">

        {{-- ===== Estadía ===== --}}
        <section class="bloque">
            <div class="bloque-cabecera">
                <h2>Estadía</h2>
            </div>

            <dl class="datos-reserva">
                <div>
                    <dt>Llegada</dt>
                    <dd>{{ ucfirst($reserva->fecha_entrada->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY')) }}
                        <span>desde las {{ $hotel['check_in'] }}</span></dd>
                </div>
                <div>
                    <dt>Salida</dt>
                    <dd>{{ ucfirst($reserva->fecha_salida->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY')) }}
                        <span>hasta las {{ $hotel['check_out'] }}</span></dd>
                </div>
                <div>
                    <dt>Noches</dt>
                    <dd>{{ $noches }}</dd>
                </div>
                <div>
                    <dt>Titular</dt>
                    <dd>{{ $reserva->nombre_titular }}
                        <span>{{ $reserva->cantidad_personas }} {{ $reserva->cantidad_personas === 1 ? 'persona' : 'personas' }}</span></dd>
                </div>
                @if ($rol === 'RECEPCIONISTA')
                    <div>
                        <dt>Reservada por</dt>
                        <dd>
                            @if ($reserva->agencia) Agencia {{ $reserva->agencia->nombre }} <span>NIT {{ $reserva->agencia->nit }}</span>
                            @elseif ($reserva->cliente) {{ $reserva->cliente->nombreCompleto() }} <span>{{ $reserva->cliente->email }}</span>
                            @else Recepción <span>sin cuenta de cliente</span>
                            @endif
                        </dd>
                    </div>
                @endif
                @if ($reserva->observacion)
                    <div class="dato-ancho">
                        <dt>Observación</dt>
                        <dd>{{ $reserva->observacion }}</dd>
                    </div>
                @endif
            </dl>
        </section>

        {{-- ===== Habitaciones ===== --}}
        <section class="bloque">
            <div class="bloque-cabecera">
                <h2>Habitaciones</h2>
                <p>El precio por noche se fijó al reservar: no cambia aunque cambie la tarifa.</p>
            </div>

            @if ($etiquetas)
                <ul class="resumen-etiquetas resumen-etiquetas-claras">
                    @foreach ($etiquetas as $e)
                        <li><i data-lucide="badge-percent"></i>{{ $e }}</li>
                    @endforeach
                </ul>
            @endif

            <div class="tabla-envoltura">
                <table class="tabla tabla-compacta">
                    <thead>
                        <tr>
                            <th>Habitación</th>
                            <th>Tipo</th>
                            <th>Por noche</th>
                            <th>Estadía</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reserva->habitaciones as $rh)
                            <tr>
                                <td><span class="numero-habitacion">{{ $rh->habitacion->numero }}</span></td>
                                <td>
                                    {{ $rh->habitacion->tipo->nombreVisible() }}
                                    <span class="dato-secundario">Piso {{ $rh->habitacion->piso }}</span>
                                </td>
                                <td><span class="precio">{{ Moneda::formato($rh->precio_noche_aplicado) }}</span></td>
                                <td><span class="precio">{{ Moneda::formato(Disponibilidad::calcularEstadia((float) $rh->precio_noche_aplicado, $noches)['total']) }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    {{-- ===== Cuenta y cancelación ===== --}}
    <aside class="resumen-reserva">
        <div class="resumen-tarjeta">
            <p class="resumen-titulo">Cuenta</p>

            <p class="resumen-total">
                <strong>{{ Moneda::formato($total) }}</strong>
                <span>Total de la estadía</span>
            </p>

            <dl class="resumen-cuenta">
                <div>
                    <dt>Adelanto ({{ Disponibilidad::ADELANTO }} %)</dt>
                    <dd>{{ Moneda::formato($adelanto) }}</dd>
                </div>
                <div>
                    <dt>Pagado</dt>
                    <dd>{{ Moneda::formato($pagado) }}</dd>
                </div>
                <div class="resumen-cuenta-falta">
                    <dt>Falta pagar</dt>
                    <dd>{{ Moneda::formato(max(0, $total - $pagado)) }}</dd>
                </div>
            </dl>

            @if ($estado === EstadoReserva::PENDIENTE)
                <p class="resumen-aviso">
                    <i data-lucide="hourglass"></i>
                    Para confirmarla falta pagar el adelanto de {{ Moneda::formato(max(0, $adelanto - $pagado)) }}.
                </p>
            @endif

            @if ($rol === 'RECEPCIONISTA' && $reserva->comision)
                <p class="resumen-nota">Comisión de {{ Moneda::formato($reserva->comision->monto) }} por confirmarla.</p>
            @endif
        </div>

        <div class="politica-cancelacion @if ($cancelable && ! $devuelve) politica-vencida @endif">
            <i data-lucide="{{ $cancelable ? ($devuelve ? 'shield-check' : 'shield-alert') : 'info' }}"></i>
            <p>
                @if ($cancelable && $devuelve)
                    <strong>Cancelación con devolución total</strong>
                    hasta el {{ $limite->format('d/m/Y') }} a las {{ $limite->format('H:i') }}
                    ({{ Reserva::HORAS_CANCELACION }} h antes del check-in).
                @elseif ($cancelable)
                    <strong>Ya pasó el plazo de devolución</strong>
                    ({{ $limite->format('d/m/Y H:i') }}). Si se cancela, lo pagado no se devuelve.
                @elseif ($estado === EstadoReserva::CANCELADA)
                    <strong>Reserva cancelada.</strong> Las habitaciones quedaron libres.
                @else
                    Esta reserva ya no se puede cancelar.
                @endif
            </p>
        </div>
    </aside>
</div>
@endsection
