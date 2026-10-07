@extends('layouts.panel')

@section('titulo', 'Agencias')

@section('panel')
@php $hayFiltros = $buscar !== '' || $estado; @endphp

<header class="cabecera-pagina">
    <div>
        <nav class="migas" aria-label="Ruta">
            <a href="{{ route('admin.inicio') }}">Inicio</a>
            <i data-lucide="chevron-right"></i>
            <span>Agencias</span>
        </nav>
        <h1>Agencias</h1>
        <p>{{ $totales['activas'] }} activas de {{ $totales['todas'] }} en total.</p>
    </div>

    <a href="{{ route('admin.agencias.create') }}" class="btn btn-primario">
        <i data-lucide="building-2"></i>
        Nueva agencia
    </a>
</header>

<section class="bloque">

    {{-- ===== Buscador y filtro (busca mientras escribes) ===== --}}
    <form class="filtros" method="GET" action="{{ route('admin.agencias.index') }}"
          role="search" data-busqueda-vivo>
        <div class="buscador-campo">
            <i data-lucide="search"></i>
            <input type="search" name="buscar" value="{{ $buscar }}" autocomplete="off"
                   placeholder="Buscar por agencia, NIT, contacto, correo o teléfono"
                   aria-label="Buscar agencia">
        </div>

        <select name="estado" aria-label="Filtrar por estado">
            <option value="">Todos los estados</option>
            <option value="activas" @selected($estado === 'activas')>Activas</option>
            <option value="inactivas" @selected($estado === 'inactivas')>Inactivas</option>
        </select>

        <button type="submit" class="btn btn-secundario">Buscar</button>
    </form>

    {{-- ===== Resultados: esta parte se reemplaza en la búsqueda en vivo ===== --}}
    <div data-resultados aria-live="polite">

        @if ($agencias->isEmpty())

            <div class="vacio-panel">
                <span class="vacio-icono"><i data-lucide="building-2"></i></span>

                @if ($hayFiltros)
                    <h3>No hay resultados</h3>
                    <p>Prueba con otro nombre, NIT o quita los filtros.</p>
                    <a href="{{ route('admin.agencias.index') }}" class="btn btn-secundario">Quitar filtros</a>
                @else
                    <h3>Aún no hay agencias</h3>
                    <p>Registra la primera agencia para que pueda reservar habitaciones.</p>
                    <a href="{{ route('admin.agencias.create') }}" class="btn btn-primario">
                        <i data-lucide="building-2"></i> Nueva agencia
                    </a>
                @endif
            </div>

        @else

            @if ($hayFiltros)
                <p class="conteo-resultados">
                    {{ $agencias->total() }}
                    {{ $agencias->total() === 1 ? 'resultado' : 'resultados' }}
                    · <a href="{{ route('admin.agencias.index') }}">Quitar filtros</a>
                </p>
            @endif

            <div class="tabla-envoltura">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Agencia</th>
                            <th>Persona de contacto</th>
                            <th>Teléfono</th>
                            <th>Estado</th>
                            <th><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($agencias as $agencia)
                            <tr @class(['fila-inactiva' => ! $agencia->activa])>
                                {{-- Empresa --}}
                                <td>
                                    <div class="persona">
                                        <span class="avatar avatar-tabla avatar-agencia">
                                            <i data-lucide="building-2"></i>
                                        </span>
                                        <div>
                                            <strong>{{ $agencia->nombre }}</strong>
                                            <span>NIT {{ $agencia->nit ?? '—' }}</span>
                                        </div>
                                    </div>
                                </td>

                                {{-- Contacto --}}
                                <td>
                                    <div class="contacto-celda">
                                        <strong>{{ $agencia->usuario->nombreCompleto() }}</strong>
                                        <span>{{ $agencia->usuario->email }}</span>
                                    </div>
                                </td>

                                {{-- Teléfono de la empresa (o del contacto si no tiene) --}}
                                <td>
                                    {{ \App\Support\Paises::formatearTelefono($agencia->telefono)
                                        ?? \App\Support\Paises::formatearTelefono($agencia->usuario->telefono)
                                        ?? '—' }}
                                </td>

                                <td>
                                    @if ($agencia->activa)
                                        <span class="estado estado-activo">Activa</span>
                                    @else
                                        <span class="estado estado-inactivo">Inactiva</span>
                                    @endif
                                </td>

                                <td>
                                    <div class="acciones">
                                        <a href="{{ route('admin.agencias.edit', $agencia) }}" class="boton-accion">
                                            <i data-lucide="pencil"></i>
                                            <span>Editar</span>
                                        </a>

                                        @php
                                            $pregunta = $agencia->activa
                                                ? "¿Desactivar {$agencia->nombre}? Su cuenta ya no podrá iniciar sesión ni reservar."
                                                : "¿Activar {$agencia->nombre}? Podrá volver a iniciar sesión y reservar.";
                                        @endphp

                                        <form method="POST"
                                              action="{{ route('admin.agencias.estado', $agencia) }}"
                                              data-confirmar="{{ $pregunta }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" @class([
                                                'boton-accion',
                                                'boton-peligro' => $agencia->activa,
                                                'boton-exito'   => ! $agencia->activa,
                                            ])>
                                                <i data-lucide="{{ $agencia->activa ? 'circle-off' : 'circle-check' }}"></i>
                                                <span>{{ $agencia->activa ? 'Desactivar' : 'Activar' }}</span>
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
                {{ $agencias->links('pagination::default') }}
            </div>

        @endif

    </div>
    {{-- ===== Fin de resultados ===== --}}

</section>
@endsection