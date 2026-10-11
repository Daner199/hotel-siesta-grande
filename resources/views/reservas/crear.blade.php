@extends('layouts.panel')

{{--
    Nueva reserva (la comparten cliente, agencia y recepción).
    $modo = 'tipos'        → cliente y agencia eligen tipo + cantidad (el sistema asigna los números)
    $modo = 'habitaciones' → recepción elige los números exactos
    El resumen de la derecha lo calcula Laravel (ruta .cotizar) y lo pinta public/js/reservas.js.
--}}
@php
    $hotel   = \App\Models\Hotel::datos();
    $listado = $rol === 'RECEPCIONISTA' ? 'Reservas' : ($rol === 'AGENCIA' ? 'Reservas de la agencia' : 'Mis reservas');
    $hoy     = today()->toDateString();
    $llegada = old('fecha_entrada', $inicial['llegada'] ?? null);
    $salida  = old('fecha_salida', $inicial['salida'] ?? null);
@endphp

@section('titulo', 'Nueva reserva')

@push('scripts')
    <script src="{{ asset('js/validacion.js') }}"></script>
    <script src="{{ asset('js/reservas.js') }}"></script>
@endpush

@section('panel')
<header class="cabecera-pagina">
    <div>
        <nav class="migas" aria-label="Ruta">
            <a href="{{ route($inicio) }}">Inicio</a>
            <i data-lucide="chevron-right"></i>
            <a href="{{ route($prefijo . '.index') }}">{{ $listado }}</a>
            <i data-lucide="chevron-right"></i>
            <span>Nueva</span>
        </nav>
        <h1>Nueva reserva</h1>
        <p>
            @if ($modo === 'tipos')
                Elige las fechas y cuántas habitaciones de cada tipo; los números se asignan solos.
            @else
                Elige las fechas y las habitaciones exactas. Solo se pueden marcar las que están libres.
            @endif
        </p>
    </div>
</header>

<form method="POST" action="{{ route($prefijo . '.store') }}" class="reserva-nueva" novalidate
      data-validar-form data-reserva data-cotizar="{{ route($prefijo . '.cotizar') }}">
    @csrf

    <div class="reserva-columnas">
        <div class="reserva-pasos">

            {{-- ===== 1. Fechas ===== --}}
            <section class="bloque">
                <fieldset class="seccion-form">
                    <legend>
                        <span class="seccion-icono"><i data-lucide="calendar-range"></i></span>
                        Fechas de la estadía
                    </legend>

                    <div class="rejilla-form">
                        <div class="campo">
                            <label for="fecha_entrada">Llegada <span class="opcional">(check-in {{ $hotel['check_in'] }})</span></label>
                            <input type="date" id="fecha_entrada" name="fecha_entrada" required min="{{ $hoy }}"
                                   value="{{ $llegada }}" data-validar="fecha" data-vacio="Elige la fecha de llegada.">
                            @error('fecha_entrada') <p class="campo-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="campo">
                            <label for="fecha_salida">Salida <span class="opcional">(check-out {{ $hotel['check_out'] }})</span></label>
                            <input type="date" id="fecha_salida" name="fecha_salida" required min="{{ $hoy }}"
                                   value="{{ $salida }}" data-validar="fecha" data-vacio="Elige la fecha de salida.">
                            @error('fecha_salida') <p class="campo-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </fieldset>
            </section>

            {{-- ===== 2. Habitaciones ===== --}}
            <section class="bloque">
                <fieldset class="seccion-form">
                    <legend>
                        <span class="seccion-icono"><i data-lucide="bed-double"></i></span>
                        Habitaciones
                    </legend>

                    @error('habitaciones') <p class="campo-error campo-error-bloque">{{ $message }}</p> @enderror
                    @error('tipos') <p class="campo-error campo-error-bloque">{{ $message }}</p> @enderror

                    @if ($modo === 'tipos')
                        <div class="tipos-reserva">
                            @foreach ($tipos as $tipo)
                                @php
                                    $valor = (int) old("tipos.{$tipo->id}", ($inicial['tipo'] ?? null) === $tipo->id ? 1 : 0);
                                @endphp
                                <article class="tipo-reserva" data-tipo="{{ $tipo->id }}">
                                    <div class="tipo-reserva-foto">
                                        @if ($tipo->fotoPrincipal)
                                            <img src="{{ $tipo->fotoPrincipal->url() }}" alt="" loading="lazy">
                                        @else
                                            <span class="foto-vacia"><i data-lucide="bed-double"></i></span>
                                        @endif
                                    </div>

                                    <div class="tipo-reserva-datos">
                                        <h3>{{ $tipo->nombreVisible() }}</h3>
                                        <p class="dato-icono">
                                            <i data-lucide="users"></i>
                                            Hasta {{ $tipo->capacidad }} {{ $tipo->capacidad === 1 ? 'persona' : 'personas' }}
                                        </p>
                                        <p class="tipo-reserva-precio"><strong data-precio>—</strong> por noche</p>
                                        <p class="tipo-reserva-libres" data-libres>Elige las fechas</p>
                                    </div>

                                    <div class="contador" role="group" aria-label="Habitaciones {{ $tipo->nombreVisible() }}">
                                        <button type="button" class="boton-icono-claro" data-menos aria-label="Una menos">
                                            <i data-lucide="minus"></i>
                                        </button>
                                        <input type="number" name="tipos[{{ $tipo->id }}]" value="{{ $valor }}"
                                               min="0" max="{{ $valor }}" step="1" inputmode="numeric"
                                               aria-label="Cantidad de habitaciones {{ $tipo->nombreVisible() }}">
                                        <button type="button" class="boton-icono-claro" data-mas aria-label="Una más">
                                            <i data-lucide="plus"></i>
                                        </button>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @else
                        @php $marcadas = array_map('intval', (array) old('habitaciones', [])); @endphp
                        @foreach ($tipos as $tipo)
                            <div class="grupo-habitaciones" data-tipo="{{ $tipo->id }}">
                                <h3>
                                    {{ $tipo->nombreVisible() }}
                                    <span>
                                        Hasta {{ $tipo->capacidad }} {{ $tipo->capacidad === 1 ? 'persona' : 'personas' }}
                                        · <strong data-precio>—</strong> por noche
                                        · <span data-libres>elige las fechas</span>
                                    </span>
                                </h3>
                                <div class="habitaciones-reserva">
                                    @foreach ($tipo->habitaciones as $h)
                                        <label class="habitacion-chip" data-habitacion="{{ $h->id }}">
                                            <input type="checkbox" name="habitaciones[]" value="{{ $h->id }}"
                                                   @checked(in_array($h->id, $marcadas, true))>
                                            <span>{{ $h->numero }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @endif
                </fieldset>
            </section>

            {{-- ===== 3. Titular ===== --}}
            <section class="bloque">
                <fieldset class="seccion-form">
                    <legend>
                        <span class="seccion-icono"><i data-lucide="user-round"></i></span>
                        Titular de la reserva
                    </legend>

                    <div class="rejilla-form">
                        @if ($rol === 'CLIENTE')
                            <div class="campo">
                                <span class="etiqueta-campo">Titular</span>
                                <p class="dato-fijo">{{ auth()->user()->nombreCompleto() }}</p>
                            </div>
                        @else
                            <div class="campo">
                                <label for="nombre_titular">Nombre completo del titular</label>
                                <input type="text" id="nombre_titular" name="nombre_titular" maxlength="100" required
                                       autocomplete="off" placeholder="Carlos Pérez" value="{{ old('nombre_titular') }}"
                                       data-validar="letras" data-vacio="Escribe el nombre completo del titular.">
                                @error('nombre_titular') <p class="campo-error">{{ $message }}</p> @enderror
                            </div>
                        @endif

                        <div class="campo">
                            <label for="cantidad_personas">Cantidad de personas</label>
                            <input type="number" id="cantidad_personas" name="cantidad_personas" min="1" max="100"
                                   step="1" inputmode="numeric" required
                                   value="{{ old('cantidad_personas', 1) }}"
                                   data-validar="entero" data-vacio="Indica cuántas personas se alojan.">
                            @error('cantidad_personas') <p class="campo-error">{{ $message }}</p> @enderror
                        </div>

                        @if ($rol === 'RECEPCIONISTA')
                            <div class="campo">
                                <label for="correo_cliente">Correo del cliente <span class="opcional">(opcional)</span></label>
                                <input type="email" id="correo_cliente" name="correo_cliente" maxlength="150"
                                       autocomplete="off" placeholder="cliente@gmail.com" value="{{ old('correo_cliente') }}"
                                       data-validar="email">
                                @error('correo_cliente') <p class="campo-error">{{ $message }}</p> @enderror
                                <p class="ayuda-campo">Si tiene cuenta, verá la reserva en su panel.</p>
                            </div>
                        @endif
                    </div>

                    <div class="campo">
                        <label for="observacion">Observación <span class="opcional">(opcional)</span></label>
                        <textarea id="observacion" name="observacion" rows="2" maxlength="500"
                                  placeholder="Ej.: Llegan tarde, cerca de las 22:00.">{{ old('observacion') }}</textarea>
                        @error('observacion') <p class="campo-error">{{ $message }}</p> @enderror
                    </div>
                </fieldset>
            </section>
        </div>

        {{-- ===== Resumen en vivo ===== --}}
        <aside class="resumen-reserva" aria-labelledby="titulo-resumen">
            <div class="resumen-tarjeta" data-resumen-caja aria-live="polite">
                <p class="resumen-titulo" id="titulo-resumen">Resumen</p>

                <p class="resumen-vacio" data-resumen-vacio>Elige las fechas y al menos una habitación.</p>
                <p class="resumen-error" data-resumen-error hidden></p>

                <div data-resumen hidden>
                    <p class="resumen-estadia" data-resumen-estadia></p>
                    <ul class="resumen-etiquetas" data-resumen-etiquetas></ul>

                    <p class="resumen-total">
                        <s data-resumen-sin-descuento></s>
                        <strong data-resumen-total></strong>
                        <span>Total de la estadía</span>
                    </p>

                    <p class="resumen-adelanto">
                        <span>Adelanto para confirmar ({{ \App\Support\Disponibilidad::ADELANTO }} %)</span>
                        <strong data-resumen-adelanto></strong>
                    </p>

                    <p class="resumen-aviso" data-resumen-capacidad hidden></p>
                </div>

                <button type="submit" class="btn btn-tajibo btn-bloque" data-reservar disabled>
                    <i data-lucide="calendar-check"></i>
                    Reservar
                </button>

                <p class="resumen-nota">
                    Queda pendiente hasta pagar el adelanto. Cancelación con devolución total hasta
                    {{ \App\Models\Reserva::HORAS_CANCELACION }} h antes del check-in.
                </p>
            </div>
        </aside>
    </div>
</form>
@endsection
