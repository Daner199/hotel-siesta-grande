@extends('layouts.panel')

{{-- Check-in / Check-out: pestañas, buscador en vivo y una acción clara por fila --}}
@use('App\Models\EstadoReserva')
@use('App\Support\Estadias')
@use('App\Support\Moneda')

@section('titulo', 'Check-in / Check-out')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/pagos.css') }}">
@endpush

@section('panel')
<header class="cabecera-pagina">
    <div>
        <nav class="migas" aria-label="Ruta">
            <a href="{{ route('recepcion.inicio') }}">Inicio</a>
            <i data-lucide="chevron-right"></i>
            <span>Check-in / Check-out</span>
        </nav>
        <h1>Check-in / Check-out</h1>
    </div>
    @if ($pestana === 'noshow')
        <form method="POST" action="{{ route('recepcion.estadias.no-show') }}">
            @csrf
            <button type="submit" class="btn btn-secundario">Procesar no-show</button>
        </form>
    @endif
</header>

<section class="caja-plana">
    <nav class="pestanas-planas" aria-label="Listas">
        @foreach (\App\Http\Controllers\Recepcion\EstadiaController::PESTANAS as $clave => $texto)
            <a href="{{ route('recepcion.estadias.index', array_filter(['pestana' => $clave, 'buscar' => $buscar])) }}"
               @class(['activa' => $pestana === $clave]) @if ($pestana === $clave) aria-current="page" @endif>
                {{ $texto }} <span class="pestana-numero">{{ $conteos[$clave] }}</span>
            </a>
        @endforeach
    </nav>

    <form class="filtros filtros-compactos" method="GET" action="{{ route('recepcion.estadias.index') }}" role="search" data-busqueda-vivo>
        <input type="hidden" name="pestana" value="{{ $pestana }}">
        <div class="buscador-campo">
            <i data-lucide="search"></i>
            <input type="search" name="buscar" value="{{ $buscar }}" autocomplete="off" autofocus
                   placeholder="Código, titular o habitación" aria-label="Buscar reserva">
        </div>
    </form>

    <div data-resultados aria-live="polite">
        @if ($reservas->isEmpty())
            <p class="vacio-plano">{{ $buscar !== '' ? 'Sin resultados.' : 'No hay reservas en esta lista.' }}</p>
        @else
            <div class="tabla-envoltura">
                <table class="tabla tabla-compacta tabla-recepcion">
                    <thead>
                        <tr>
                            <th>Hab.</th>
                            <th>Reserva</th>
                            <th>{{ $pestana === 'llegadas' || $pestana === 'noshow' ? 'Llegada' : 'Entrada' }}</th>
                            <th>Salida</th>
                            <th>{{ $pestana === 'noshow' ? 'Estado' : ($pestana === 'llegadas' ? 'Adelanto' : 'Saldo') }}</th>
                            <th><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reservas as $r)
                            @php
                                $numeros = $r->habitaciones->map(fn ($rh) => $rh->habitacion->numero);
                                $tipos   = $r->habitaciones->map(fn ($rh) => $rh->habitacion->tipo->nombreVisible())->unique()->join(', ');
                            @endphp
                            <tr>
                                <td><span class="numeros-habitacion">@foreach ($numeros as $n)<span>{{ $n }}</span>@endforeach</span></td>
                                <td>
                                    <strong>{{ $r->nombre_titular }}</strong>
                                    <span class="dato-secundario">
                                        {{ $r->codigo }} · {{ $tipos }} · {{ $r->cantidad_personas }} {{ $r->cantidad_personas === 1 ? 'persona' : 'personas' }}
                                        @if ($r->agencia) · {{ $r->agencia->nombre }} @endif
                                    </span>
                                </td>
                                <td>
                                    @if ($pestana === 'alojados' || $pestana === 'salidas')
                                        {{ $r->check_in_en?->format('d/m H:i') }}
                                    @else
                                        {{ $r->fecha_entrada->format('d/m/Y') }}
                                        @if ($pestana === 'llegadas' && $r->fecha_entrada->lt(today()))
                                            <span class="dato-secundario dato-alerta">Plazo hasta hoy {{ Estadias::HORA_NO_SHOW }}:00</span>
                                        @endif
                                    @endif
                                </td>
                                <td>{{ $r->fecha_salida->format('d/m/Y') }}</td>
                                <td>
                                    @if ($pestana === 'noshow')
                                        <span class="estado estado-reserva estado-reserva-{{ strtolower($r->estado->nombre) }}">{{ $r->estado->etiqueta() }}</span>
                                    @elseif ($pestana === 'llegadas')
                                        @if ($r->esEstado(EstadoReserva::PENDIENTE))
                                            <span class="dato-alerta">Falta {{ Moneda::formato(max(0, $r->adelanto() - $r->totalPagado())) }}</span>
                                        @else
                                            Pagado
                                        @endif
                                    @else
                                        @php $saldo = $r->saldo(); @endphp
                                        <span @class(['precio', 'dato-alerta' => $saldo > 0])>{{ Moneda::formato($saldo) }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="acciones">
                                        @if ($pestana === 'llegadas')
                                            @if ($r->esEstado(EstadoReserva::PENDIENTE))
                                                <a href="{{ route('recepcion.reservas.pagar', $r) }}" class="boton-accion">Cobrar adelanto</a>
                                            @elseif (! Estadias::motivoSinCheckIn($r))
                                                <form method="POST" action="{{ route('recepcion.estadias.check-in', $r) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="boton-accion boton-principal">Check-in</button>
                                                </form>
                                            @endif
                                        @elseif ($pestana === 'salidas')
                                            <a href="{{ route('recepcion.estadias.cuenta', $r) }}" class="boton-accion boton-principal">Check-out</a>
                                        @endif
                                        <a href="{{ route('recepcion.estadias.cuenta', $r) }}" class="boton-accion">Cuenta</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="paginacion">
                {{ $reservas->links('pagination::default') }}
            </div>
        @endif
    </div>
</section>
@endsection
