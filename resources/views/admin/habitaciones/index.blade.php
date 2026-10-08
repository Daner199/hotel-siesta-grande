@extends('layouts.panel')

@section('titulo', 'Habitaciones')

@use('App\Support\Moneda')

@section('panel')
@php $hayFiltros = $buscar !== '' || array_filter($filtros); @endphp

<header class="cabecera-pagina">
    <div>
        <nav class="migas" aria-label="Ruta">
            <a href="{{ route('admin.inicio') }}">Inicio</a>
            <i data-lucide="chevron-right"></i>
            <span>Habitaciones</span>
        </nav>
        <h1>Habitaciones</h1>
        <p>{{ $total }} habitaciones en {{ $pisos->count() }} {{ $pisos->count() === 1 ? 'piso' : 'pisos' }}.</p>

        {{-- Resumen por estado: cada uno filtra la lista --}}
        <ul class="resumen-estados" aria-label="Habitaciones por estado">
            @foreach ($estados as $e)
                <li>
                    <a href="{{ route('admin.habitaciones.index', ['estado' => $e->id]) }}"
                       class="chip-estado chip-{{ strtolower($e->nombre) }}">
                        <strong>{{ $resumen[$e->id] ?? 0 }}</strong> {{ $e->etiqueta() }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>

    <a href="{{ route('admin.habitaciones.create') }}" class="btn btn-primario">
        <i data-lucide="plus"></i>
        Nueva habitación
    </a>
</header>

<section class="bloque">

    {{-- ===== Buscador y filtros (busca mientras escribes) ===== --}}
    <form class="filtros" method="GET" action="{{ route('admin.habitaciones.index') }}"
          role="search" data-busqueda-vivo>
        <div class="buscador-campo">
            <i data-lucide="search"></i>
            <input type="search" name="buscar" value="{{ $buscar }}" autocomplete="off"
                   placeholder="Buscar por número o descripción" aria-label="Buscar habitación">
        </div>

        <select name="tipo" aria-label="Filtrar por tipo">
            <option value="">Todos los tipos</option>
            @foreach ($tipos as $t)
                <option value="{{ $t->id }}" @selected($filtros['tipo'] === $t->id)>{{ $t->nombre }}</option>
            @endforeach
        </select>

        <select name="piso" aria-label="Filtrar por piso">
            <option value="">Todos los pisos</option>
            @foreach ($pisos as $p)
                <option value="{{ $p }}" @selected($filtros['piso'] === $p)>Piso {{ $p }}</option>
            @endforeach
        </select>

        <select name="estado" aria-label="Filtrar por estado">
            <option value="">Todos los estados</option>
            @foreach ($estados as $e)
                <option value="{{ $e->id }}" @selected($filtros['estado'] === $e->id)>{{ $e->etiqueta() }}</option>
            @endforeach
        </select>

        <button type="submit" class="btn btn-secundario">Buscar</button>
    </form>

    {{-- ===== Resultados: esta parte se reemplaza en la búsqueda en vivo ===== --}}
    <div data-resultados aria-live="polite">

        @if ($habitaciones->isEmpty())

            <div class="vacio-panel">
                <span class="vacio-icono"><i data-lucide="bed-double"></i></span>

                @if ($hayFiltros)
                    <h3>No hay resultados</h3>
                    <p>Prueba con otro número o quita los filtros.</p>
                    <a href="{{ route('admin.habitaciones.index') }}" class="btn btn-secundario">Quitar filtros</a>
                @else
                    <h3>Aún no hay habitaciones</h3>
                    <p>Registra la primera habitación del hotel.</p>
                    <a href="{{ route('admin.habitaciones.create') }}" class="btn btn-primario">
                        <i data-lucide="plus"></i> Nueva habitación
                    </a>
                @endif
            </div>

        @else

            @if ($hayFiltros)
                <p class="conteo-resultados">
                    {{ $habitaciones->total() }}
                    {{ $habitaciones->total() === 1 ? 'resultado' : 'resultados' }}
                    · <a href="{{ route('admin.habitaciones.index') }}">Quitar filtros</a>
                </p>
            @endif

            <div class="tabla-envoltura">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Habitación</th>
                            <th>Tipo</th>
                            <th>Tarifa hoy</th>
                            <th>Estado</th>
                            <th><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($habitaciones as $habitacion)
                            @php $nombreEstado = strtolower($habitacion->estado->nombre); @endphp

                            <tr @class(['fila-inactiva' => $habitacion->estado->nombre !== 'ACTIVA'])>
                                <td>
                                    <div class="persona">
                                        <span class="numero-habitacion">{{ $habitacion->numero }}</span>
                                        <div>
                                            <strong>Piso {{ $habitacion->piso }}</strong>
                                            <span>{{ $habitacion->descripcion ?? 'Sin descripción' }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <strong>{{ $habitacion->tipo->nombre }}</strong>
                                    @unless ($habitacion->tipo->activo)
                                        <span class="etiqueta">Tipo inactivo</span>
                                    @endunless
                                    <span class="dato-icono dato-secundario">
                                        <i data-lucide="users"></i>
                                        {{ $habitacion->tipo->capacidad }}
                                        {{ $habitacion->tipo->capacidad === 1 ? 'persona' : 'personas' }}
                                    </span>
                                </td>

                                <td>
                                    @if ($tarifas[$habitacion->tipo_habitacion_id] ?? null)
                                        <span class="precio">{{ Moneda::formato($tarifas[$habitacion->tipo_habitacion_id]) }}</span>
                                    @else
                                        <span class="etiqueta etiqueta-alerta">Sin tarifa</span>
                                    @endif
                                </td>

                                <td>
                                    {{-- Cambia el estado al elegir otra opción --}}
                                    <form method="POST" action="{{ route('admin.habitaciones.estado', $habitacion) }}"
                                          class="form-estado">
                                        @csrf
                                        @method('PATCH')
                                        <select name="estado_habitacion_id" data-autoenviar
                                                class="selector-estado selector-{{ $nombreEstado }}"
                                                aria-label="Estado de la habitación {{ $habitacion->numero }}">
                                            @foreach ($estados as $e)
                                                <option value="{{ $e->id }}" @selected($e->id === $habitacion->estado_habitacion_id)>
                                                    {{ $e->etiqueta() }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <noscript><button type="submit" class="boton-accion">Cambiar</button></noscript>
                                    </form>
                                </td>

                                <td>
                                    <div class="acciones">
                                        <a href="{{ route('admin.habitaciones.edit', $habitacion) }}" class="boton-accion">
                                            <i data-lucide="pencil"></i>
                                            <span>Editar</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="paginacion">
                {{ $habitaciones->links('pagination::default') }}
            </div>

        @endif

    </div>
    {{-- ===== Fin de resultados ===== --}}

    <p class="nota-pie">
        <i data-lucide="info"></i>
        Las habitaciones no se eliminan. Si una ya no se usa, cámbiala a «Fuera de servicio».
        Solo las habitaciones activas se pueden reservar.
    </p>

</section>
@endsection
