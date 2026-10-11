@extends('layouts.panel')

{{-- Recepción: editar titular, personas y observación (fechas y habitaciones no se cambian) --}}
@php
    $capacidad = $reserva->habitaciones->sum(fn ($rh) => $rh->habitacion->tipo->capacidad);
@endphp

@section('titulo', "Editar {$reserva->codigo}")

@push('scripts')
    <script src="{{ asset('js/validacion.js') }}"></script>
@endpush

@section('panel')
<header class="cabecera-pagina">
    <div>
        <nav class="migas" aria-label="Ruta">
            <a href="{{ route($inicio) }}">Inicio</a>
            <i data-lucide="chevron-right"></i>
            <a href="{{ route($prefijo . '.index') }}">Reservas</a>
            <i data-lucide="chevron-right"></i>
            <a href="{{ route($prefijo . '.show', $reserva) }}">{{ $reserva->codigo }}</a>
            <i data-lucide="chevron-right"></i>
            <span>Editar</span>
        </nav>
        <h1>Editar {{ $reserva->codigo }}</h1>
        <p>
            {{ $reserva->fecha_entrada->format('d/m/Y') }} al {{ $reserva->fecha_salida->format('d/m/Y') }}
            · habitaciones {{ $reserva->habitaciones->pluck('habitacion.numero')->join(', ') }}
        </p>
    </div>
</header>

<form class="bloque formulario-panel" method="POST" novalidate data-validar-form
      action="{{ route($prefijo . '.update', $reserva) }}">
    @csrf
    @method('PUT')

    <fieldset class="seccion-form">
        <legend>
            <span class="seccion-icono"><i data-lucide="user-round"></i></span>
            Titular de la reserva
        </legend>

        <div class="rejilla-form">
            <div class="campo">
                <label for="nombre_titular">Nombre completo del titular</label>
                <input type="text" id="nombre_titular" name="nombre_titular" maxlength="100" required autocomplete="off"
                       value="{{ old('nombre_titular', $reserva->nombre_titular) }}"
                       data-validar="letras" data-vacio="Escribe el nombre completo del titular.">
                @error('nombre_titular') <p class="campo-error">{{ $message }}</p> @enderror
            </div>

            <div class="campo">
                <label for="cantidad_personas">Cantidad de personas</label>
                <input type="number" id="cantidad_personas" name="cantidad_personas" min="1" max="{{ $capacidad }}"
                       step="1" inputmode="numeric" required
                       value="{{ old('cantidad_personas', $reserva->cantidad_personas) }}"
                       data-validar="entero" data-vacio="Indica cuántas personas se alojan.">
                @error('cantidad_personas') <p class="campo-error">{{ $message }}</p> @enderror
                <p class="ayuda-campo">Máximo {{ $capacidad }}</p>
            </div>
        </div>

        <div class="campo">
            <label for="observacion">Observación <span class="opcional">(opcional)</span></label>
            <textarea id="observacion" name="observacion" rows="3" maxlength="500">{{ old('observacion', $reserva->observacion) }}</textarea>
            @error('observacion') <p class="campo-error">{{ $message }}</p> @enderror
        </div>
    </fieldset>

    <div class="acciones-form">
        <a href="{{ route($prefijo . '.show', $reserva) }}" class="btn btn-secundario">Volver</a>
        <button type="submit" class="btn btn-primario">
            <i data-lucide="save"></i>
            Guardar cambios
        </button>
    </div>
</form>
@endsection
