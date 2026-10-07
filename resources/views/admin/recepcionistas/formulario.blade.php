@extends('layouts.panel')

@php $editando = (bool) $recepcionista; @endphp

@section('titulo', $editando ? 'Editar recepcionista' : 'Nuevo recepcionista')

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/libphonenumber-js@1/bundle/libphonenumber-js.min.js"></script>
    <script src="{{ asset('js/validacion.js') }}"></script>
@endpush

@section('panel')
<header class="cabecera-pagina">
    <div>
        <nav class="migas" aria-label="Ruta">
            <a href="{{ route('admin.inicio') }}">Inicio</a>
            <i data-lucide="chevron-right"></i>
            <a href="{{ route('admin.recepcionistas.index') }}">Recepcionistas</a>
            <i data-lucide="chevron-right"></i>
            <span>{{ $editando ? 'Editar' : 'Nuevo' }}</span>
        </nav>
        <h1>{{ $editando ? $recepcionista->nombreCompleto() : 'Nuevo recepcionista' }}</h1>
        <p>
            {{ $editando
                ? 'Actualiza sus datos o cambia su contraseña.'
                : 'La persona iniciará sesión con este correo y contraseña.' }}
        </p>
    </div>
</header>

<form class="bloque formulario-panel" method="POST" novalidate data-validar-form
      action="{{ $editando
          ? route('admin.recepcionistas.update', $recepcionista)
          : route('admin.recepcionistas.store') }}">
    @csrf
    @if ($editando) @method('PUT') @endif

    {{-- ===== Datos personales ===== --}}
    <fieldset class="seccion-form">
        <legend>
            <span class="seccion-icono"><i data-lucide="user-round"></i></span>
            Datos personales
        </legend>

        <div class="rejilla-form">
            <div class="campo">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" maxlength="100" required
                       value="{{ old('nombre', $recepcionista?->nombre) }}"
                       data-validar="letras" data-vacio="Escribe el nombre.">
                @error('nombre') <p class="campo-error">{{ $message }}</p> @enderror
            </div>

            <div class="campo">
                <label for="apellido">Apellido</label>
                <input type="text" id="apellido" name="apellido" maxlength="100" required
                       value="{{ old('apellido', $recepcionista?->apellido) }}"
                       data-validar="letras" data-vacio="Escribe el apellido.">
                @error('apellido') <p class="campo-error">{{ $message }}</p> @enderror
            </div>

            <div class="campo">
                <label for="telefono">Teléfono <span class="opcional">(opcional)</span></label>
                <div class="telefono">
                    <select id="telefono_pais" name="telefono_pais" aria-label="País del teléfono">
                        @foreach ($paises as $iso => $etiqueta)
                            <option value="{{ $iso }}" @selected(old('telefono_pais', $telefono['pais']) === $iso)>
                                {{ $etiqueta }}
                            </option>
                        @endforeach
                    </select>
                    <input type="tel" id="telefono" name="telefono" inputmode="numeric" maxlength="15"
                           placeholder="71234567"
                           value="{{ old('telefono', $telefono['numero']) }}"
                           data-validar="telefono" data-pais="telefono_pais">
                </div>
                @error('telefono_pais') <p class="campo-error">{{ $message }}</p> @enderror
                @error('telefono') <p class="campo-error">{{ $message }}</p> @enderror
            </div>

            <div class="campo">
                <label for="email">Correo</label>
                <input type="email" id="email" name="email" maxlength="150" required
                       placeholder="nombre@gmail.com"
                       value="{{ old('email', $recepcionista?->email) }}"
                       data-validar="email" data-vacio="Escribe el correo.">
                @error('email') <p class="campo-error">{{ $message }}</p> @enderror
            </div>
        </div>
    </fieldset>

    {{-- ===== Contraseña ===== --}}
    <fieldset class="seccion-form">
        <legend>
            <span class="seccion-icono"><i data-lucide="key-round"></i></span>
            {{ $editando ? 'Cambiar contraseña' : 'Contraseña de acceso' }}
        </legend>

        @if ($editando)
            <p class="ayuda-form">Déjalo vacío para mantener la contraseña actual.</p>
        @endif

        <div class="rejilla-form">
            <div class="campo">
                <label for="password">{{ $editando ? 'Nueva contraseña' : 'Contraseña' }}</label>
                <input type="password" id="password" name="password" maxlength="72"
                       autocomplete="new-password" @required(! $editando)
                       data-validar="password" data-vacio="Crea una contraseña.">
                <div class="medidor" aria-hidden="true"><span></span></div>
                <p class="medidor-texto" aria-live="polite"></p>
                @error('password') <p class="campo-error">{{ $message }}</p> @enderror
            </div>

            <div class="campo">
                <label for="password_confirmation">Repite la contraseña</label>
                <input type="password" id="password_confirmation" name="password_confirmation"
                       maxlength="72" autocomplete="new-password"
                       data-validar="confirmar" data-igual="password">
            </div>
        </div>
        <p class="ayuda-form">Mínimo 8 caracteres, con al menos una letra y un número.</p>
    </fieldset>

    {{-- ===== Botones ===== --}}
    <div class="acciones-form">
        <a href="{{ route('admin.recepcionistas.index') }}" class="btn btn-secundario">Cancelar</a>
        <button type="submit" class="btn btn-primario">
            <i data-lucide="save"></i>
            {{ $editando ? 'Guardar cambios' : 'Crear recepcionista' }}
        </button>
    </div>
</form>
@endsection