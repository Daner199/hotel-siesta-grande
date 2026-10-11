@extends('layouts.panel')

{{-- Lista de reservas (la comparten cliente, agencia y recepción) --}}
@use('App\Support\Moneda')

@php
    $titulos = [
        'CLIENTE'       => ['Mis reservas', 'Tus reservas y su estado. Toca una para ver el detalle.'],
        'AGENCIA'       => ['Reservas de la agencia', 'Todas las reservas que hizo tu agencia para sus clientes.'],
        'RECEPCIONISTA' => ['Reservas', 'Todas las reservas del hotel: de clientes, de agencias y de recepción.'],
    ];
    [$titulo, $resumen] = $titulos[$rol];
    $hayFiltros = $buscar !== '' || $estado;
    $verOrigen = $rol === 'RECEPCIONISTA';
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

    <a href="{{ route($prefijo . '.create') }}" class="btn btn-primario">
        <i data-lucide="calendar-plus"></i>
        Nueva reserva
    </a>
</header>

<section class="bloque">
    <form class="filtros" method="GET" action="{{ route($prefijo . '.index') }}" role="search" data-busqueda-vivo>
        <div class="buscador-campo">
            <i data-lucide="search"></i>
            <input type="search" name="buscar" value="{{ $buscar }}" autocomplete="off"
                   placeholder="{{ $rol === 'CLIENTE' ? 'Buscar por código' : 'Buscar por código, titular' . ($verOrigen ? ' o agencia' : '') }}"
                   aria-label="Buscar reserva">
        </div>

        <select name="estado" aria-label="Filtrar por estado">
            <option value="">Todos los estados</option>
            @foreach ($estados as $e)
                <option value="{{ $e->id }}" @selected($estado === $e->id)>{{ $e->etiqueta() }}</option>
            @endforeach
        </select>

        <button type="submit" class="btn btn-secundario">Buscar</button>
    </form>

    <div data-resultados aria-live="polite">
        @if ($reservas->isEmpty())
            <div class="vacio-panel">
                <span class="vacio-icono"><i data-lucide="calendar-check"></i></span>
                @if ($hayFiltros)
                    <h3>No hay resultados</h3>
                    <p>Prueba con otro código o quita los filtros.</p>
                    <a href="{{ route($prefijo . '.index') }}" class="btn btn-secundario">Quitar filtros</a>
                @else
                    <h3>Todavía no hay reservas</h3>
                    <p>Elige las fechas y las habitaciones: el total se calcula al instante.</p>
                    <a href="{{ route($prefijo . '.create') }}" class="btn btn-primario">
                        <i data-lucide="calendar-plus"></i> Nueva reserva
                    </a>
                @endif
            </div>
        @else
            @if ($hayFiltros)
                <p class="conteo-resultados">
                    {{ $reservas->total() }} {{ $reservas->total() === 1 ? 'resultado' : 'resultados' }}
                    · <a href="{{ route($prefijo . '.index') }}">Quitar filtros</a>
                </p>
            @endif

            <div class="tabla-envoltura">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Reserva</th>
                            <th>{{ $rol === 'CLIENTE' ? 'Personas' : 'Titular' }}</th>
                            <th>Fechas</th>
                            <th>Habitaciones</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reservas as $r)
                            @php $noches = $r->noches(); @endphp
                            <tr @class(['fila-inactiva' => in_array($r->estado->nombre, ['CANCELADA', 'NO_SHOW'])])>
                                <td>
                                    <strong class="codigo-reserva">{{ $r->codigo }}</strong>
                                    <span class="dato-secundario">Hecha el {{ $r->fecha_creacion->format('d/m/Y') }}</span>
                                </td>

                                <td>
                                    @if ($rol === 'CLIENTE')
                                        {{ $r->cantidad_personas }} {{ $r->cantidad_personas === 1 ? 'persona' : 'personas' }}
                                    @else
                                        <strong>{{ $r->nombre_titular }}</strong>
                                        <span class="dato-secundario">
                                            @if ($verOrigen)
                                                @if ($r->agencia) Agencia {{ $r->agencia->nombre }}
                                                @elseif ($r->cliente) Cliente · {{ $r->cliente->email }}
                                                @else Recepción
                                                @endif
                                            @else
                                                {{ $r->cantidad_personas }} {{ $r->cantidad_personas === 1 ? 'persona' : 'personas' }}
                                            @endif
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    {{ $r->fecha_entrada->format('d/m/Y') }}
                                    <i data-lucide="arrow-right" class="flecha-fechas" aria-label="al"></i>
                                    {{ $r->fecha_salida->format('d/m/Y') }}
                                    <span class="dato-secundario">{{ $noches }} {{ $noches === 1 ? 'noche' : 'noches' }}</span>
                                </td>

                                <td>
                                    <span class="numeros-habitacion">
                                        @foreach ($r->habitaciones as $rh)
                                            <span>{{ $rh->habitacion->numero }}</span>
                                        @endforeach
                                    </span>
                                </td>

                                <td><span class="precio">{{ Moneda::formato($r->total()) }}</span></td>

                                <td>
                                    <span class="estado estado-reserva estado-reserva-{{ strtolower($r->estado->nombre) }}">
                                        {{ $r->estado->etiqueta() }}
                                    </span>
                                </td>

                                <td>
                                    <div class="acciones">
                                        <a href="{{ route($prefijo . '.show', $r) }}" class="boton-accion">
                                            <i data-lucide="eye"></i>
                                            <span>Ver</span>
                                        </a>
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
