@extends('layouts.panel')

@php $editando = (bool) $tipo; @endphp

@section('titulo', $editando ? 'Editar tipo' : 'Nuevo tipo')

@push('scripts')
    <script src="{{ asset('js/validacion.js') }}"></script>
@endpush

@section('panel')
<header class="cabecera-pagina">
    <div>
        <nav class="migas" aria-label="Ruta">
            <a href="{{ route('admin.inicio') }}">Inicio</a>
            <i data-lucide="chevron-right"></i>
            <a href="{{ route('admin.tipos.index') }}">Tipos y tarifas</a>
            <i data-lucide="chevron-right"></i>
            <span>{{ $editando ? 'Editar' : 'Nuevo' }}</span>
        </nav>
        <h1>{{ $editando ? $tipo->nombre : 'Nuevo tipo de habitación' }}</h1>
        <p>
            {{ $editando
                ? 'Cambia el nombre, la capacidad o la descripción. Las tarifas se manejan aparte.'
                : 'Después de crearlo te llevaremos a registrar su tarifa por noche.' }}
        </p>
    </div>
</header>

<form class="bloque formulario-panel" method="POST" novalidate data-validar-form
      action="{{ $editando ? route('admin.tipos.update', $tipo) : route('admin.tipos.store') }}">
    @csrf
    @if ($editando) @method('PUT') @endif

    <fieldset class="seccion-form">
        <legend>
            <span class="seccion-icono"><i data-lucide="bed-double"></i></span>
            Datos del tipo
        </legend>

        <div class="rejilla-form">
            <div class="campo">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" maxlength="50" required
                       class="mayusculas" placeholder="SUITE PRESIDENCIAL"
                       value="{{ old('nombre', $tipo?->nombre) }}"
                       data-validar="tipo" data-vacio="Escribe el nombre del tipo.">
                @error('nombre') <p class="campo-error">{{ $message }}</p> @enderror
            </div>

            <div class="campo">
                <label for="capacidad">Capacidad <span class="opcional">(personas)</span></label>
                <input type="number" id="capacidad" name="capacidad" min="1" max="10" step="1"
                       inputmode="numeric" required
                       value="{{ old('capacidad', $tipo?->capacidad) }}"
                       data-validar="entero" data-vacio="Indica cuántas personas caben.">
                @error('capacidad') <p class="campo-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="campo">
            <label for="descripcion">Descripción <span class="opcional">(opcional)</span></label>
            <textarea id="descripcion" name="descripcion" rows="3" maxlength="500"
                      placeholder="Ej.: Habitación amplia con sala, vista a la piscina y jacuzzi.">{{ old('descripcion', $tipo?->descripcion) }}</textarea>
            @error('descripcion') <p class="campo-error">{{ $message }}</p> @enderror
        </div>
        <p class="ayuda-form">La descripción se mostrará en la página pública del hotel.</p>
    </fieldset>

    <div class="acciones-form">
        <a href="{{ route('admin.tipos.index') }}" class="btn btn-secundario">Cancelar</a>
        <button type="submit" class="btn btn-primario">
            <i data-lucide="save"></i>
            {{ $editando ? 'Guardar cambios' : 'Crear tipo' }}
        </button>
    </div>
</form>
@endsection
