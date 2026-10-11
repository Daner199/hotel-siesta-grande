@extends('layouts.panel')

{{-- Lista de pagos ("Mis pagos" del cliente, "Pagos" de la agencia y de recepción) --}}
@use('App\Support\Moneda')

@php
    $titulos = [
        'CLIENTE'       => ['Mis pagos', 'Lo que pagaste por tus reservas.'],
        'AGENCIA'       => ['Pagos de la agencia', 'Los pagos de todas las reservas de tu agencia.'],
        'RECEPCIONISTA' => ['Pagos', 'Todos los pagos del hotel: en línea y en recepción.'],
    ];
    [$titulo, $resumen] = $titulos[$rol];
    $hayFiltros = $buscar !== '' || $estado || $metodo;
@endphp

@section('titulo', $titulo)

@section('panel')
<header class="cabecera-pagina">
    <div>
        <nav class="migas" aria-label="Ruta">
            <a href="{{ route($inicio) }}">Inicio</a>
            <i data-lucide="chevron-right"></i>
            <span>{{ $titulo }}</span>
        </nav>
        <h1>{{ $titulo }}</h1>
        <p>{{ $resumen }}</p>
    </div>
</header>

<section class="estadisticas">
    <div class="estadistica estadistica-verde">
        <span class="estadistica-icono"><i data-lucide="circle-check"></i></span>
        <div>
            <p class="estadistica-valor estadistica-monto">{{ Moneda::formato($totales->aprobado) }}</p>
            <p class="estadistica-texto">Pagado{{ $hayFiltros ? ' (con los filtros)' : '' }}</p>
        </div>
    </div>
    <div class="estadistica estadistica-dorado">
        <span class="estadistica-icono"><i data-lucide="undo-2"></i></span>
        <div>
            <p class="estadistica-valor estadistica-monto">{{ Moneda::formato($totales->devuelto) }}</p>
            <p class="estadistica-texto">Devuelto por cancelaciones</p>
        </div>
    </div>
</section>

<section class="bloque">
    <form class="filtros" method="GET" action="{{ route($rutaPagos) }}" role="search" data-busqueda-vivo>
        <div class="buscador-campo">
            <i data-lucide="search"></i>
            <input type="search" name="buscar" value="{{ $buscar }}" autocomplete="off"
                   placeholder="Buscar por código de reserva o referencia" aria-label="Buscar pago">
        </div>

        <select name="estado" aria-label="Filtrar por estado">
            <option value="">Todos los estados</option>
            <option value="APROBADO" @selected($estado === 'APROBADO')>Aprobados</option>
            <option value="RECHAZADO" @selected($estado === 'RECHAZADO')>Rechazados</option>
            <option value="DEVUELTO" @selected($estado === 'DEVUELTO')>Devueltos</option>
        </select>

        <select name="metodo" aria-label="Filtrar por método">
            <option value="">Todos los métodos</option>
            @foreach ($metodos as $m)
                <option value="{{ $m->id }}" @selected($metodo === $m->id)>{{ $m->etiqueta() }}</option>
            @endforeach
        </select>

        <button type="submit" class="btn btn-secundario">Buscar</button>
    </form>

    <div data-resultados aria-live="polite">
        @if ($pagos->isEmpty())
            <div class="vacio-panel">
                <span class="vacio-icono"><i data-lucide="wallet"></i></span>
                @if ($hayFiltros)
                    <h3>No hay resultados</h3>
                    <p>Prueba con otro código o quita los filtros.</p>
                    <a href="{{ route($rutaPagos) }}" class="btn btn-secundario">Quitar filtros</a>
                @else
                    <h3>Todavía no hay pagos</h3>
                    <p>Los pagos se hacen desde el detalle de cada reserva.</p>
                    <a href="{{ route($prefijo . '.index') }}" class="btn btn-secundario">Ver reservas</a>
                @endif
            </div>
        @else
            @if ($hayFiltros)
                <p class="conteo-resultados">
                    {{ $pagos->total() }} {{ $pagos->total() === 1 ? 'resultado' : 'resultados' }}
                    · <a href="{{ route($rutaPagos) }}">Quitar filtros</a>
                </p>
            @endif

            @include('pagos.lista', ['pagos' => $pagos, 'conReserva' => true])

            <div class="paginacion">
                {{ $pagos->links('pagination::default') }}
            </div>
        @endif
    </div>
</section>
@endsection
