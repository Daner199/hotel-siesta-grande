@extends('layouts.panel')

{{-- Consumos cargados (restaurante y frigobar) --}}
@use('App\Support\Moneda')

@section('titulo', 'Consumos')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/pagos.css') }}">
@endpush

@section('panel')
<header class="cabecera-pagina">
    <div>
        <nav class="migas" aria-label="Ruta">
            <a href="{{ route('recepcion.inicio') }}">Inicio</a>
            <i data-lucide="chevron-right"></i>
            <span>Consumos</span>
        </nav>
        <h1>Consumos</h1>
    </div>
    <a href="{{ route('recepcion.estadias.index', ['pestana' => 'alojados']) }}" class="btn btn-primario">Cargar a un huésped</a>
</header>

<section class="caja-plana">
    <form class="filtros filtros-compactos" method="GET" action="{{ route('recepcion.consumos.index') }}" role="search" data-busqueda-vivo>
        <div class="buscador-campo">
            <i data-lucide="search"></i>
            <input type="search" name="buscar" value="{{ $buscar }}" autocomplete="off"
                   placeholder="Detalle, código o titular" aria-label="Buscar consumo">
        </div>
        <select name="tipo" aria-label="Tipo">
            <option value="">Todos</option>
            @foreach ($tipos as $t)
                <option value="{{ $t->id }}" @selected($tipo === $t->id)>{{ $t->etiqueta() }}</option>
            @endforeach
        </select>
    </form>

    <div data-resultados aria-live="polite">
        @if ($consumos->isEmpty())
            <p class="vacio-plano">Sin consumos.</p>
        @else
            <p class="conteo-resultados">{{ $consumos->total() }} {{ $consumos->total() === 1 ? 'consumo' : 'consumos' }} · {{ Moneda::formato($total) }}</p>
            <div class="tabla-envoltura">
                <table class="tabla tabla-compacta">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Hab.</th>
                            <th>Huésped</th>
                            <th>Detalle</th>
                            <th>Cant.</th>
                            <th>Importe</th>
                            <th>Cargó</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($consumos as $c)
                            <tr>
                                <td>{{ $c->fecha_consumo->format('d/m H:i') }}</td>
                                <td>{{ $c->reserva->habitaciones->map(fn ($rh) => $rh->habitacion->numero)->join(', ') }}</td>
                                <td>
                                    <a href="{{ route('recepcion.estadias.cuenta', $c->reserva) }}" class="enlace-reserva">{{ $c->reserva->nombre_titular }}</a>
                                    <span class="dato-secundario">{{ $c->reserva->codigo }}</span>
                                </td>
                                <td>{{ $c->descripcion }} <span class="dato-secundario">{{ $c->tipo->etiqueta() }}</span></td>
                                <td>{{ $c->cantidad }}</td>
                                <td><span class="precio">{{ Moneda::formato($c->importe()) }}</span></td>
                                <td>{{ $c->registradoPor->nombre }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="paginacion">{{ $consumos->links('pagination::default') }}</div>
        @endif
    </div>
</section>
@endsection
