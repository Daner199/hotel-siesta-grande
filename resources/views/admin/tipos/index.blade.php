@extends('layouts.panel')

@section('titulo', 'Tipos y tarifas')

@use('App\Support\Moneda')

@section('panel')
<header class="cabecera-pagina">
    <div>
        <nav class="migas" aria-label="Ruta">
            <a href="{{ route('admin.inicio') }}">Inicio</a>
            <i data-lucide="chevron-right"></i>
            <span>Tipos y tarifas</span>
        </nav>
        <h1>Tipos y tarifas</h1>
        <p>
            {{ $tipos->where('activo', true)->count() }} tipos activos de {{ $tipos->count() }}
            · {{ $habitaciones }} habitaciones en total.
        </p>
    </div>

    <a href="{{ route('admin.tipos.create') }}" class="btn btn-primario">
        <i data-lucide="plus"></i>
        Nuevo tipo
    </a>
</header>

<section class="bloque">

    @if ($tipos->isEmpty())

        <div class="vacio-panel">
            <span class="vacio-icono"><i data-lucide="bed-double"></i></span>
            <h3>Aún no hay tipos de habitación</h3>
            <p>Crea el primero (por ejemplo SIMPLE) y luego registra su tarifa.</p>
            <a href="{{ route('admin.tipos.create') }}" class="btn btn-primario">
                <i data-lucide="plus"></i> Nuevo tipo
            </a>
        </div>

    @else

        <div class="tabla-envoltura">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Capacidad</th>
                        <th>Habitaciones</th>
                        <th>Tarifa por noche</th>
                        <th>Estado</th>
                        <th><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tipos as $tipo)
                        <tr @class(['fila-inactiva' => ! $tipo->activo])>
                            <td>
                                <div class="persona">
                                    @if ($tipo->fotoPrincipal)
                                        <img src="{{ $tipo->fotoPrincipal->url() }}" alt="" class="miniatura-tipo"
                                             loading="lazy" decoding="async">
                                    @else
                                        <span class="avatar avatar-tabla avatar-tipo" aria-hidden="true">
                                            <i data-lucide="bed-double"></i>
                                        </span>
                                    @endif
                                    <div>
                                        <strong>{{ $tipo->nombre }}</strong>
                                        <span>{{ $tipo->descripcion ?? 'Sin descripción' }}</span>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <span class="dato-icono">
                                    <i data-lucide="users"></i>
                                    {{ $tipo->capacidad }} {{ $tipo->capacidad === 1 ? 'persona' : 'personas' }}
                                </span>
                            </td>

                            <td>{{ $tipo->habitaciones_count }}</td>

                            <td>
                                @if ($tipo->vigente)
                                    <strong class="precio">{{ Moneda::formato($tipo->vigente->precio_noche) }}</strong>
                                @else
                                    <span class="etiqueta etiqueta-alerta">Sin tarifa</span>
                                @endif

                                @if ($tipo->programada)
                                    <span class="precio-proximo">
                                        <i data-lucide="calendar-clock"></i>
                                        {{ Moneda::formato($tipo->programada->precio_noche) }}
                                        desde el {{ $tipo->programada->fecha_desde->format('d/m/Y') }}
                                    </span>
                                @endif
                            </td>

                            <td>
                                @if ($tipo->activo)
                                    <span class="estado estado-activo">Activo</span>
                                @else
                                    <span class="estado estado-inactivo">Inactivo</span>
                                @endif
                            </td>

                            <td>
                                <div class="acciones">
                                    <a href="{{ route('admin.tipos.tarifas', $tipo) }}" class="boton-accion">
                                        <i data-lucide="tags"></i>
                                        <span>Tarifas</span>
                                    </a>

                                    <a href="{{ route('admin.tipos.fotos', $tipo) }}" class="boton-accion">
                                        <i data-lucide="images"></i>
                                        <span>Fotos</span>
                                    </a>

                                    <a href="{{ route('admin.tipos.edit', $tipo) }}" class="boton-accion">
                                        <i data-lucide="pencil"></i>
                                        <span>Editar</span>
                                    </a>

                                    @php
                                        $n = $tipo->habitaciones_count;
                                        $pregunta = $tipo->activo
                                            ? "¿Desactivar {$tipo->nombre}? No se ofrecerá en reservas nuevas"
                                                . ($n ? " y sus {$n} habitaciones no se podrán reservar." : '.')
                                            : "¿Activar {$tipo->nombre}? Volverá a ofrecerse en reservas.";
                                    @endphp

                                    <form method="POST" action="{{ route('admin.tipos.estado', $tipo) }}"
                                          data-confirmar="{{ $pregunta }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" @class([
                                            'boton-accion',
                                            'boton-peligro' => $tipo->activo,
                                            'boton-exito'   => ! $tipo->activo,
                                        ])>
                                            <i data-lucide="{{ $tipo->activo ? 'eye-off' : 'eye' }}"></i>
                                            <span>{{ $tipo->activo ? 'Desactivar' : 'Activar' }}</span>
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.tipos.destroy', $tipo) }}"
                                          data-confirmar="¿Eliminar el tipo {{ $tipo->nombre }}? Esto no se puede deshacer.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="boton-accion boton-peligro"
                                                title="Eliminar" aria-label="Eliminar {{ $tipo->nombre }}">
                                            <i data-lucide="trash-2"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="nota-pie">
            <i data-lucide="info"></i>
            Un tipo solo se puede eliminar si no tiene habitaciones, tarifas ni promociones.
            Si ya se usa, desactívalo.
        </p>

    @endif

</section>
@endsection
