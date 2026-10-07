@extends('layouts.panel')

@php $editando = (bool) $agencia; @endphp

@section('titulo', $editando ? 'Editar agencia' : 'Nueva agencia')

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
            <a href="{{ route('admin.agencias.index') }}">Agencias</a>
            <i data-lucide="chevron-right"></i>
            <span>{{ $editando ? 'Editar' : 'Nueva' }}</span>
        </nav>
        <h1>{{ $editando ? $agencia->nombre : 'Nueva agencia' }}</h1>
        <p>
            {{ $editando
                ? 'Actualiza los datos de la agencia, de su contacto o su contraseña.'
                : 'La persona de contacto iniciará sesión con su correo y esta contraseña.' }}
        </p>
    </div>
</header>

<form class="bloque formulario-panel" method="POST" novalidate data-validar-form
      action="{{ $editando
          ? route('admin.agencias.update', $agencia)
          : route('admin.agencias.store') }}">
    @csrf
    @if ($editando) @method('PUT') @endif

    {{-- ===== 1. Datos de la agencia (tabla agencia) ===== --}}
    <fieldset class="seccion-form">
        <legend>
            <span class="seccion-icono"><i data-lucide="building-2"></i></span>
            Datos de la agencia
        </legend>

        <div class="rejilla-form">
            <div class="campo">
                <label for="agencia_nombre">Nombre de la agencia</label>
                <input type="text" id="agencia_nombre" name="agencia_nombre" maxlength="150" required
                       placeholder="Viajes Bolivia S.R.L."
                       value="{{ old('agencia_nombre', $agencia?->nombre) }}"
                       data-validar="empresa" data-vacio="Escribe el nombre de la agencia.">
                @error('agencia_nombre') <p class="campo-error">{{ $message }}</p> @enderror
            </div>

            <div class="campo">
                <label for="nit">NIT</label>
                <input type="text" id="nit" name="nit" inputmode="numeric" maxlength="12" required
                       placeholder="1020304050"
                       value="{{ old('nit', $agencia?->nit) }}"
                       data-validar="nit" data-vacio="Escribe el NIT de la agencia.">
                @error('nit') <p class="campo-error">{{ $message }}</p> @enderror
            </div>

            <div class="campo">
                <label for="agencia_telefono">Teléfono de la agencia <span class="opcional">(opcional)</span></label>
                <div class="telefono">
                    <select id="agencia_telefono_pais" name="agencia_telefono_pais" aria-label="País del teléfono de la agencia">
                        @foreach ($paises as $iso => $etiqueta)
                            <option value="{{ $iso }}" @selected(old('agencia_telefono_pais', $telefonoAgencia['pais']) === $iso)>
                                {{ $etiqueta }}
                            </option>
                        @endforeach
                    </select>
                    <input type="tel" id="agencia_telefono" name="agencia_telefono" inputmode="numeric" maxlength="15"
                           placeholder="33456789"
                           value="{{ old('agencia_telefono', $telefonoAgencia['numero']) }}"
                           data-validar="telefono" data-pais="agencia_telefono_pais">
                </div>
                @error('agencia_telefono_pais') <p class="campo-error">{{ $message }}</p> @enderror
                @error('agencia_telefono') <p class="campo-error">{{ $message }}</p> @enderror
            </div>
        </div>
    </fieldset>

    {{-- ===== 2. Persona de contacto (tabla usuario) ===== --}}
    <fieldset class="seccion-form">
        <legend>
            <span class="seccion-icono"><i data-lucide="user-round"></i></span>
            Persona de contacto
        </legend>
        <p class="ayuda-form">Es quien inicia sesión y hace las reservas en nombre de la agencia.</p>

        <div class="rejilla-form">
            <div class="campo">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" maxlength="100" required
                       value="{{ old('nombre', $agencia?->usuario->nombre) }}"
                       data-validar="letras" data-vacio="Escribe el nombre de la persona de contacto.">
                @error('nombre') <p class="campo-error">{{ $message }}</p> @enderror
            </div>

            <div class="campo">
                <label for="apellido">Apellido</label>
                <input type="text" id="apellido" name="apellido" maxlength="100" required
                       value="{{ old('apellido', $agencia?->usuario->apellido) }}"
                       data-validar="letras" data-vacio="Escribe el apellido de la persona de contacto.">
                @error('apellido') <p class="campo-error">{{ $message }}</p> @enderror
            </div>

            <div class="campo">
                <label for="telefono">Celular <span class="opcional">(opcional)</span></label>
                <div class="telefono">
                    <select id="telefono_pais" name="telefono_pais" aria-label="País del celular">
                        @foreach ($paises as $iso => $etiqueta)
                            <option value="{{ $iso }}" @selected(old('telefono_pais', $telefonoContacto['pais']) === $iso)>
                                {{ $etiqueta }}
                            </option>
                        @endforeach
                    </select>
                    <input type="tel" id="telefono" name="telefono" inputmode="numeric" maxlength="15"
                           placeholder="71234567"
                           value="{{ old('telefono', $telefonoContacto['numero']) }}"
                           data-validar="telefono" data-pais="telefono_pais">
                </div>
                @error('telefono_pais') <p class="campo-error">{{ $message }}</p> @enderror
                @error('telefono') <p class="campo-error">{{ $message }}</p> @enderror
            </div>

            <div class="campo">
                <label for="email">Correo para iniciar sesión</label>
                <input type="email" id="email" name="email" maxlength="150" required
                       placeholder="reservas@agencia.com"
                       value="{{ old('email', $agencia?->usuario->email) }}"
                       data-validar="email" data-vacio="Escribe el correo.">
                @error('email') <p class="campo-error">{{ $message }}</p> @enderror
            </div>
        </div>
    </fieldset>

    {{-- ===== 3. Contraseña ===== --}}
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
        <a href="{{ route('admin.agencias.index') }}" class="btn btn-secundario">Cancelar</a>
        <button type="submit" class="btn btn-primario">
            <i data-lucide="save"></i>
            {{ $editando ? 'Guardar cambios' : 'Registrar agencia' }}
        </button>
    </div>
</form>
@endsection