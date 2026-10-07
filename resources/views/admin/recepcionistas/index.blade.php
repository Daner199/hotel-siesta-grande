@extends('layouts.panel')

@section('titulo', 'Recepcionistas')

@section('panel')
@php $hayFiltros = $buscar !== '' || $estado; @endphp

<header class="cabecera-pagina">
    <div>
        <nav class="migas" aria-label="Ruta">
            <a href="{{ route('admin.inicio') }}">Inicio</a>
            <i data-lucide="chevron-right"></i>
            <span>Recepcionistas</span>
        </nav>
        <h1>Recepcionistas</h1>
        <p>{{ $totales['activos'] }} activos de {{ $totales['todos'] }} en total.</p>
    </div>

    <a href="{{ route('admin.recepcionistas.create') }}" class="btn btn-primario">
        <i data-lucide="user-plus"></i>
        Nuevo recepcionista
    </a>
</header>

<section class="bloque">

    {{-- ===== Buscador y filtro (busca mientras escribes) ===== --}}
    <form class="filtros" method="GET" action="{{ route('admin.recepcionistas.index') }}"
          role="search" data-busqueda-vivo>
        <div class="buscador-campo">
            <i data-lucide="search"></i>
            <input type="search" name="buscar" value="{{ $buscar }}" autocomplete="off"
                   placeholder="Buscar por nombre, correo o teléfono" aria-label="Buscar recepcionista">
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

        @if ($recepcionistas->isEmpty())

            {{-- Estado vacío --}}
            <div class="vacio-panel">
                <span class="vacio-icono"><i data-lucide="concierge-bell"></i></span>

                @if ($hayFiltros)
                    <h3>No hay resultados</h3>
                    <p>Prueba con otro nombre o quita los filtros.</p>
                    <a href="{{ route('admin.recepcionistas.index') }}" class="btn btn-secundario">
                        Quitar filtros
                    </a>
                @else
                    <h3>Aún no hay recepcionistas</h3>
                    <p>Crea la primera cuenta para el personal de recepción.</p>
                    <a href="{{ route('admin.recepcionistas.create') }}" class="btn btn-primario">
                        <i data-lucide="user-plus"></i> Nuevo recepcionista
                    </a>
                @endif
            </div>

        @else

            {{-- Cantidad de resultados (solo cuando hay búsqueda o filtro) --}}
            @if ($hayFiltros)
                <p class="conteo-resultados">
                    {{ $recepcionistas->total() }}
                    {{ $recepcionistas->total() === 1 ? 'resultado' : 'resultados' }}
                    · <a href="{{ route('admin.recepcionistas.index') }}">Quitar filtros</a>
                </p>
            @endif

            {{-- Tabla --}}
            <div class="tabla-envoltura">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Recepcionista</th>
                            <th>Teléfono</th>
                            <th>Estado</th>
                            <th>Registrado</th>
                            <th><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recepcionistas as $recepcionista)
                            <tr @class(['fila-inactiva' => ! $recepcionista->activo])>
                                <td>
                                    <div class="persona">
                                        <span class="avatar avatar-tabla">{{ $recepcionista->iniciales() }}</span>
                                        <div>
                                            <strong>{{ $recepcionista->nombreCompleto() }}</strong>
                                            <span>{{ $recepcionista->email }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td>{{ \App\Support\Paises::formatearTelefono($recepcionista->telefono) ?? '—' }}</td>

                                <td>
                                    @if ($recepcionista->activo)
                                        <span class="estado estado-activo">Activo</span>
                                    @else
                                        <span class="estado estado-inactivo">Inactivo</span>
                                    @endif
                                </td>

                                <td>{{ $recepcionista->created_at->format('d/m/Y') }}</td>

                                <td>
                                    <div class="acciones">
                                        <a href="{{ route('admin.recepcionistas.edit', $recepcionista) }}" class="boton-accion">
                                            <i data-lucide="pencil"></i>
                                            <span>Editar</span>
                                        </a>

                                        @php
                                            $pregunta = $recepcionista->activo
                                                ? "¿Desactivar a {$recepcionista->nombreCompleto()}? Ya no podrá iniciar sesión."
                                                : "¿Activar a {$recepcionista->nombreCompleto()}? Podrá volver a iniciar sesión.";
                                        @endphp

                                        <form method="POST"
                                              action="{{ route('admin.recepcionistas.estado', $recepcionista) }}"
                                              data-confirmar="{{ $pregunta }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" @class([
                                                'boton-accion',
                                                'boton-peligro' => $recepcionista->activo,
                                                'boton-exito'   => ! $recepcionista->activo,
                                            ])>
                                                <i data-lucide="{{ $recepcionista->activo ? 'user-x' : 'user-check' }}"></i>
                                                <span>{{ $recepcionista->activo ? 'Desactivar' : 'Activar' }}</span>
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
                {{ $recepcionistas->links('pagination::default') }}
            </div>

        @endif

    </div>
    {{-- ===== Fin de resultados ===== --}}

</section>
@endsection