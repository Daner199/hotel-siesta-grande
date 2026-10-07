@extends('layouts.panel')

@section('titulo', 'Clientes')

@section('panel')
@php $hayFiltros = $buscar !== '' || $estado; @endphp

<header class="cabecera-pagina">
    <div>
        <nav class="migas" aria-label="Ruta">
            <a href="{{ route('admin.inicio') }}">Inicio</a>
            <i data-lucide="chevron-right"></i>
            <span>Clientes</span>
        </nav>
        <h1>Clientes</h1>
        <p>{{ $totales['activos'] }} activos de {{ $totales['todos'] }} en total. Se registran desde la página pública.</p>
    </div>
</header>

<section class="bloque">

    {{-- ===== Buscador y filtro (busca mientras escribes) ===== --}}
    <form class="filtros" method="GET" action="{{ route('admin.clientes.index') }}"
          role="search" data-busqueda-vivo>
        <div class="buscador-campo">
            <i data-lucide="search"></i>
            <input type="search" name="buscar" value="{{ $buscar }}" autocomplete="off"
                   placeholder="Buscar por nombre, correo o teléfono" aria-label="Buscar cliente">
        </div>

        <select name="estado" aria-label="Filtrar por estado">
            <option value="">Todos los estados</option>
            <option value="activos" @selected($estado === 'activos')>Activos</option>
            <option value="inactivos" @selected($estado === 'inactivos')>Inactivos</option>
        </select>

        <button type="submit" class="btn btn-secundario">Buscar</button>
    </form>

    {{-- ===== Resultados: esta parte se reemplaza en la búsqueda en vivo ===== --}}
    <div data-resultados aria-live="polite">

        @if ($clientes->isEmpty())

            {{-- Estado vacío --}}
            <div class="vacio-panel">
                <span class="vacio-icono"><i data-lucide="users"></i></span>

                @if ($hayFiltros)
                    <h3>No hay resultados</h3>
                    <p>Prueba con otro nombre o quita los filtros.</p>
                    <a href="{{ route('admin.clientes.index') }}" class="btn btn-secundario">
                        Quitar filtros
                    </a>
                @else
                    <h3>Aún no hay clientes</h3>
                    <p>Aparecerán aquí cuando se registren desde la página del hotel.</p>
                @endif
            </div>

        @else

            {{-- Cantidad de resultados (solo cuando hay búsqueda o filtro) --}}
            @if ($hayFiltros)
                <p class="conteo-resultados">
                    {{ $clientes->total() }}
                    {{ $clientes->total() === 1 ? 'resultado' : 'resultados' }}
                    · <a href="{{ route('admin.clientes.index') }}">Quitar filtros</a>
                </p>
            @endif

            {{-- Tabla --}}
            <div class="tabla-envoltura">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Teléfono</th>
                            <th>Estado</th>
                            <th>Registrado</th>
                            <th><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($clientes as $cliente)
                            <tr @class(['fila-inactiva' => ! $cliente->activo])>
                                <td>
                                    <div class="persona">
                                        <span class="avatar avatar-tabla">{{ $cliente->iniciales() }}</span>
                                        <div>
                                            <strong>{{ $cliente->nombreCompleto() }}</strong>
                                            <span>{{ $cliente->email }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td>{{ \App\Support\Paises::formatearTelefono($cliente->telefono) ?? '—' }}</td>

                                <td>
                                    @if ($cliente->activo)
                                        <span class="estado estado-activo">Activo</span>
                                    @else
                                        <span class="estado estado-inactivo">Inactivo</span>
                                    @endif
                                </td>

                                <td>{{ $cliente->created_at->format('d/m/Y') }}</td>

                                <td>
                                    <div class="acciones">
                                        @php
                                            $pregunta = $cliente->activo
                                                ? "¿Desactivar a {$cliente->nombreCompleto()}? Ya no podrá iniciar sesión."
                                                : "¿Activar a {$cliente->nombreCompleto()}? Podrá volver a iniciar sesión.";
                                        @endphp

                                        <form method="POST"
                                              action="{{ route('admin.clientes.estado', $cliente) }}"
                                              data-confirmar="{{ $pregunta }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" @class([
                                                'boton-accion',
                                                'boton-peligro' => $cliente->activo,
                                                'boton-exito'   => ! $cliente->activo,
                                            ])>
                                                <i data-lucide="{{ $cliente->activo ? 'user-x' : 'user-check' }}"></i>
                                                <span>{{ $cliente->activo ? 'Desactivar' : 'Activar' }}</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="paginacion">
                {{ $clientes->links('pagination::default') }}
            </div>

        @endif

    </div>
    {{-- ===== Fin de resultados ===== --}}

</section>
@endsection
