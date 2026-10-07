@extends('layouts.auth')

@section('titulo', 'Crear cuenta')
@section('frase', 'Reserva y sigue tus estadías desde un solo lugar.')

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/libphonenumber-js@1/bundle/libphonenumber-js.min.js"></script>
    <script src="{{ asset('js/validacion.js') }}"></script>
@endpush

@section('formulario')
<form class="formulario" method="POST" action="{{ route('registro.guardar') }}" novalidate data-validar-form>
    @csrf

    <h1>Crear cuenta</h1>
    <p class="formulario-intro">Con tu cuenta puedes reservar y ver el estado de tus reservas y pagos.</p>

    <div class="campo-doble">
        <div class="campo">
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" value="{{ old('nombre') }}"
                   maxlength="100" autocomplete="given-name" required autofocus
                   data-validar="letras" data-vacio="Escribe tu nombre.">
            @error('nombre') <p class="campo-error">{{ $message }}</p> @enderror
        </div>

        <div class="campo">
            <label for="apellido">Apellido</label>
            <input type="text" id="apellido" name="apellido" value="{{ old('apellido') }}"
                   maxlength="100" autocomplete="family-name" required
                   data-validar="letras" data-vacio="Escribe tu apellido.">
            @error('apellido') <p class="campo-error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="campo">
        <label for="telefono">Teléfono <span class="opcional">(opcional)</span></label>
        <div class="telefono">
            <select id="telefono_pais" name="telefono_pais" aria-label="País del teléfono">
                @foreach ($paises as $iso => $etiqueta)
                    <option value="{{ $iso }}" @selected(old('telefono_pais', 'BO') === $iso)>{{ $etiqueta }}</option>
                @endforeach
            </select>

            <input type="tel" id="telefono" name="telefono" value="{{ old('telefono') }}"
                   inputmode="numeric" maxlength="15" placeholder="71234567"
                   autocomplete="tel-national"
                   data-validar="telefono" data-pais="telefono_pais">
        </div>
        @error('telefono_pais') <p class="campo-error">{{ $message }}</p> @enderror
        @error('telefono') <p class="campo-error">{{ $message }}</p> @enderror
    </div>

    <div class="campo">
        <label for="email">Correo</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}"
               maxlength="150" autocomplete="email" placeholder="nombre@gmail.com" required
               data-validar="email" data-vacio="Escribe tu correo.">
        @error('email') <p class="campo-error">{{ $message }}</p> @enderror
    </div>

    <div class="campo-doble">
        <div class="campo">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password"
                   maxlength="72" autocomplete="new-password" required
                   data-validar="password" data-vacio="Crea una contraseña.">
            <div class="medidor" aria-hidden="true"><span></span></div>
            <p class="medidor-texto" aria-live="polite"></p>
        </div>

        <div class="campo">
            <label for="password_confirmation">Repite la contraseña</label>
            <input type="password" id="password_confirmation" name="password_confirmation"
                   maxlength="72" autocomplete="new-password" required
                   data-validar="confirmar" data-igual="password">
        </div>
    </div>
    <p class="ayuda">Mínimo 8 caracteres, con al menos una letra y un número.</p>
    @error('password')
        <p class="campo-error campo-error-suelto" data-error-de="password">{{ $message }}</p>
    @enderror

    <button type="submit" class="btn btn-primario btn-bloque">Crear cuenta</button>

    <p class="formulario-pie">
        ¿Ya tienes cuenta? <a href="{{ route('login') }}">Inicia sesión</a>
    </p>
</form>
@endsection