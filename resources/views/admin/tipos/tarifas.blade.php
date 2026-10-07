@extends('layouts.panel')

@section('titulo', "Tarifas · {$tipo->nombre}")

@use('App\Models\TarifaHabitacion')
@use('App\Support\Moneda')

@php
    $etiquetas = [
        TarifaHabitacion::VIGENTE    => 'Vigente',
        TarifaHabitacion::PROGRAMADA => 'Programada',
        TarifaHabitacion::FINALIZADA => 'Finalizada',
        TarifaHabitacion::ANULADA    => 'Anulada',
    ];
    // La tarifa que sigue a la vigente (si la vigente ya tiene fecha de fin)
    $siguiente = $vigente?->fecha_hasta
        ? $activas->first(fn ($t) => $t->fecha_desde->equalTo($vigente->fecha_hasta))
        : null;
@endphp

@push('scripts')
    <script src="{{ asset('js/validacion.js') }}"></script>
    <script>
        // Centrar la línea de tiempo en la tarifa vigente
        (() => {
            const linea = document.querySelector('[data-linea-tiempo]');
            const vigente = linea?.querySelector('[data-hito-vigente]');
            if (linea && vigente && linea.scrollWidth > linea.clientWidth) {
                linea.scrollLeft = vigente.offsetLeft - (linea.clientWidth - vigente.offsetWidth) / 2;
            }
        })();
    </script>
@endpush

@section('panel')
<header class="cabecera-pagina">
    <div>
        <nav class="migas" aria-label="Ruta">
            <a href="{{ route('admin.inicio') }}">Inicio</a>
            <i data-lucide="chevron-right"></i>
            <a href="{{ route('admin.tipos.index') }}">Tipos y tarifas</a>
            <i data-lucide="chevron-right"></i>
            <span>{{ $tipo->nombre }}</span>
        </nav>
        <h1>Tarifas de {{ $tipo->nombre }}</h1>
        <p>
            {{ $tipo->capacidad }} {{ $tipo->capacidad === 1 ? 'persona' : 'personas' }}
            · {{ $habitaciones }} {{ $habitaciones === 1 ? 'habitación' : 'habitaciones' }}
            @unless ($tipo->activo) · <span class="estado estado-inactivo">Tipo inactivo</span> @endunless
        </p>
    </div>

    <a href="{{ route('admin.tipos.edit', $tipo) }}" class="btn btn-secundario">
        <i data-lucide="pencil"></i>
        Editar tipo
    </a>
</header>

<div class="rejilla-tarifas">

    {{-- ===== Precio de hoy ===== --}}
    <section class="bloque tarifa-actual" aria-labelledby="titulo-actual">
        <p class="tarifa-actual-titulo" id="titulo-actual">Precio vigente hoy</p>

        @if ($vigente)
            <p class="tarifa-actual-precio">
                {{ Moneda::formato($vigente->precio_noche) }}
                <span>por noche</span>
            </p>
            <p class="tarifa-actual-fechas">
                <i data-lucide="calendar-range"></i>
                Desde el {{ $vigente->fecha_desde->format('d/m/Y') }}
                @if ($vigente->fecha_hasta)
                    hasta el {{ $vigente->fecha_hasta->format('d/m/Y') }}
                    @if ($siguiente)
                        · luego {{ Moneda::formato($siguiente->precio_noche) }}
                    @endif
                @else
                    · sin fecha de fin
                @endif
            </p>
        @else
            <p class="tarifa-actual-precio tarifa-actual-vacia">Sin tarifa</p>
            <p class="tarifa-actual-fechas">
                <i data-lucide="triangle-alert"></i>
                Hoy este tipo no tiene precio: no se puede reservar. Registra una tarifa.
            </p>
        @endif
    </section>

    {{-- ===== Nueva tarifa ===== --}}
    <section class="bloque" aria-labelledby="titulo-nueva">
        <form method="POST" action="{{ route('admin.tipos.tarifas.store', $tipo) }}" novalidate data-validar-form>
            @csrf
            <fieldset class="seccion-form">
                <legend id="titulo-nueva">
                    <span class="seccion-icono"><i data-lucide="calendar-plus"></i></span>
                    Programar nuevo precio
                </legend>

                <div class="rejilla-form">
                    <div class="campo">
                        <label for="precio_noche">Precio por noche</label>
                        <div class="campo-moneda">
                            <span aria-hidden="true">Bs</span>
                            <input type="text" id="precio_noche" name="precio_noche" inputmode="decimal"
                                   maxlength="12" placeholder="250,00" required
                                   value="{{ old('precio_noche') }}"
                                   data-validar="precio" data-vacio="Escribe el precio por noche.">
                        </div>
                        @error('precio_noche') <p class="campo-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="campo">
                        <label for="fecha_desde">Rige desde</label>
                        <input type="date" id="fecha_desde" name="fecha_desde" required
                               min="{{ $fechaMinima->toDateString() }}"
                               value="{{ old('fecha_desde', $fechaMinima->toDateString()) }}"
                               data-validar="fecha" data-vacio="Elige desde qué fecha rige el precio.">
                        @error('fecha_desde') <p class="campo-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <p class="ayuda-form">
                    Puede empezar el {{ $fechaMinima->format('d/m/Y') }} o después.
                    @if ($activas->isNotEmpty())
                        La tarifa anterior se cerrará ese mismo día.
                    @endif
                    Las reservas ya hechas conservan su precio.
                </p>
            </fieldset>

            <div class="acciones-form acciones-form-compactas">
                <button type="submit" class="btn btn-primario">
                    <i data-lucide="save"></i>
                    Guardar tarifa
                </button>
            </div>
        </form>
    </section>
</div>

{{-- ===== Historial: línea de tiempo ===== --}}
<section class="bloque" aria-labelledby="titulo-historial">
    <div class="bloque-cabecera">
        <h2 id="titulo-historial">Historial de precios</h2>
        <p>De la más antigua a la más reciente. Las tarifas no se editan: un cambio de precio es una tarifa nueva.</p>
    </div>

    @if ($activas->isEmpty())
        <div class="vacio-panel">
            <span class="vacio-icono"><i data-lucide="tags"></i></span>
            <h3>Todavía no hay tarifas</h3>
            <p>Registra la primera con el formulario de arriba.</p>
        </div>
    @else
        <ol class="linea-tiempo" data-linea-tiempo>
            @foreach ($activas as $tarifa)
                @php $estado = $tarifa->estado(); @endphp

                <li class="hito hito-{{ strtolower($estado) }}"
                    @if ($estado === TarifaHabitacion::VIGENTE) data-hito-vigente aria-current="true" @endif>
                    <span class="hito-punto" aria-hidden="true"></span>

                    <div class="hito-tarjeta">
                        <span class="estado-tarifa estado-tarifa-{{ strtolower($estado) }}">{{ $etiquetas[$estado] }}</span>

                        <strong class="hito-precio">{{ Moneda::formato($tarifa->precio_noche) }}</strong>

                        <span class="hito-fechas">
                            {{ $tarifa->fecha_desde->format('d/m/Y') }}
                            <i data-lucide="arrow-right" aria-label="hasta"></i>
                            {{ $tarifa->fecha_hasta?->format('d/m/Y') ?? 'sin fin' }}
                        </span>

                        @if ($estado === TarifaHabitacion::PROGRAMADA)
                            @php $dias = (int) today()->diffInDays($tarifa->fecha_desde); @endphp
                            <span class="hito-nota">Empieza en {{ $dias }} {{ $dias === 1 ? 'día' : 'días' }}</span>

                            <form method="POST" action="{{ route('admin.tipos.tarifas.anular', [$tipo, $tarifa]) }}"
                                  data-confirmar="¿Anular la tarifa de {{ Moneda::formato($tarifa->precio_noche) }} que empieza el {{ $tarifa->fecha_desde->format('d/m/Y') }}?">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="boton-accion boton-peligro">
                                    <i data-lucide="calendar-x"></i>
                                    Anular
                                </button>
                            </form>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    @endif

    @if ($anuladas->isNotEmpty())
        <details class="anuladas">
            <summary>
                <i data-lucide="chevron-right"></i>
                {{ $anuladas->count() }} {{ $anuladas->count() === 1 ? 'tarifa anulada' : 'tarifas anuladas' }}
            </summary>
            <ul>
                @foreach ($anuladas as $tarifa)
                    <li>
                        <s>{{ Moneda::formato($tarifa->precio_noche) }}</s>
                        · iba a regir desde el {{ $tarifa->fecha_desde->format('d/m/Y') }}
                    </li>
                @endforeach
            </ul>
        </details>
    @endif
</section>
@endsection
