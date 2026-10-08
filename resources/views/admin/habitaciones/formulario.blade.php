@extends('layouts.panel')

@php $editando = (bool) $habitacion; @endphp

@section('titulo', $editando ? "Habitación {$habitacion->numero}" : 'Nueva habitación')

@push('scripts')
    <script src="{{ asset('js/validacion.js') }}"></script>
@endpush

@section('panel')
<header class="cabecera-pagina">
    <div>
        <nav class="migas" aria-label="Ruta">
            <a href="{{ route('admin.inicio') }}">Inicio</a>
            <i data-lucide="chevron-right"></i>
            <a href="{{ route('admin.habitaciones.index') }}">Habitaciones</a>
            <i data-lucide="chevron-right"></i>
            <span>{{ $editando ? 'Editar' : 'Nueva' }}</span>
        </nav>
        <h1>{{ $editando ? "Habitación {$habitacion->numero}" : 'Nueva habitación' }}</h1>
        <p>
            {{ $editando
                ? 'Cambia sus datos o su estado. Las reservas ya hechas conservan su precio.'
                : 'El precio por noche lo define su tipo (en Tipos y tarifas).' }}
        </p>
    </div>
</header>

<form class="bloque formulario-panel" method="POST" novalidate data-validar-form
      action="{{ $editando ? route('admin.habitaciones.update', $habitacion) : route('admin.habitaciones.store') }}">
    @csrf
    @if ($editando) @method('PUT') @endif

    <fieldset class="seccion-form">
        <legend>
            <span class="seccion-icono"><i data-lucide="bed-double"></i></span>
            Datos de la habitación
        </legend>

        <div class="rejilla-form">
            <div class="campo">
                <label for="numero">Número</label>
                <input type="text" id="numero" name="numero" maxlength="10" required
                       class="mayusculas" placeholder="416" autocomplete="off"
                       value="{{ old('numero', $habitacion?->numero) }}"
                       data-validar="numero" data-vacio="Escribe el número de la habitación.">
                @error('numero') <p class="campo-error">{{ $message }}</p> @enderror
            </div>

            <div class="campo">
                <label for="piso">Piso</label>
                <input type="number" id="piso" name="piso" min="1" max="30" step="1"
                       inputmode="numeric" required placeholder="4"
                       value="{{ old('piso', $habitacion?->piso) }}"
                       data-validar="entero" data-vacio="Indica el piso.">
                @error('piso') <p class="campo-error">{{ $message }}</p> @enderror
            </div>

            <div class="campo">
                <label for="tipo_habitacion_id">Tipo</label>
                <select id="tipo_habitacion_id" name="tipo_habitacion_id" required>
                    <option value="">Elige un tipo</option>
                    @foreach ($tipos as $t)
                        <option value="{{ $t->id }}"
                                @selected((int) old('tipo_habitacion_id', $habitacion?->tipo_habitacion_id) === $t->id)>
                            {{ $t->nombre }} · {{ $t->capacidad }} {{ $t->capacidad === 1 ? 'persona' : 'personas' }}
                            @unless ($t->activo) (inactivo) @endunless
                        </option>
                    @endforeach
                </select>
                @error('tipo_habitacion_id') <p class="campo-error">{{ $message }}</p> @enderror
            </div>

            <div class="campo">
                <label for="estado_habitacion_id">Estado</label>
                <select id="estado_habitacion_id" name="estado_habitacion_id" required>
                    @foreach ($estados as $e)
                        <option value="{{ $e->id }}"
                                @selected((int) old('estado_habitacion_id', $habitacion?->estado_habitacion_id ?? $estados->first()->id) === $e->id)>
                            {{ $e->etiqueta() }}
                        </option>
                    @endforeach
                </select>
                @error('estado_habitacion_id') <p class="campo-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="campo">
            <label for="descripcion">Descripción <span class="opcional">(opcional)</span></label>
            <textarea id="descripcion" name="descripcion" rows="3" maxlength="500"
                      placeholder="Ej.: Vista a la piscina, cerca del ascensor.">{{ old('descripcion', $habitacion?->descripcion) }}</textarea>
            @error('descripcion') <p class="campo-error">{{ $message }}</p> @enderror
        </div>
        <p class="ayuda-form">Solo se pueden elegir tipos activos. Solo las habitaciones activas se pueden reservar.</p>
    </fieldset>

    <div class="acciones-form">
        <a href="{{ route('admin.habitaciones.index') }}" class="btn btn-secundario">Cancelar</a>
        <button type="submit" class="btn btn-primario">
            <i data-lucide="save"></i>
            {{ $editando ? 'Guardar cambios' : 'Crear habitación' }}
        </button>
    </div>
</form>

{{-- ===== Fotos propias (opcionales; solo al editar) ===== --}}
@if ($editando)
    @php $deTipo = $habitacion->tipo->fotos()->count(); @endphp

    @include('admin.fotos.galeria', [
        'fotos'   => $habitacion->fotos()->get(),
        'prefijo' => 'admin.habitaciones.fotos',
        'padre'   => $habitacion,
        'nombre'  => "la habitación {$habitacion->numero}",
        'vacio'   => $deTipo
            ? "Son opcionales (vista, balcón...). Mientras no tenga fotos propias se usan las {$deTipo} de su tipo {$habitacion->tipo->nombre}."
            : "Son opcionales (vista, balcón...). Mientras no tenga fotos propias se usan las de su tipo {$habitacion->tipo->nombre}, que todavía no tiene.",
    ])
@else
    <p class="nota-pie nota-fuera">
        <i data-lucide="images"></i>
        Después de crear la habitación podrás subirle fotos propias (opcional).
    </p>
@endif
@endsection
