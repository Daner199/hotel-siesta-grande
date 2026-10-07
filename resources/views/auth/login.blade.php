@extends('layouts.auth')

@section('titulo', 'Iniciar sesión')
@section('frase', 'Tu descanso empieza aquí.')

@section('formulario')
<form class="formulario" method="POST" action="{{ route('login.ingresar') }}" novalidate>
    @csrf

    <h1>Iniciar sesión</h1>
    <p class="formulario-intro">Entra con tu correo. Te llevamos a tu panel según tu tipo de cuenta.</p>

    @error('email')
        <div class="alerta" role="alert">{{ $message }}</div>
    @enderror

    <div class="campo">
        <label for="email">Correo</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}"
               autocomplete="email" required autofocus>
    </div>

    <div class="campo">
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password"
               autocomplete="current-password" required>
        @error('password')
            <p class="campo-error">{{ $message }}</p>
        @enderror
    </div>

    <label class="recordar">
        <input type="checkbox" name="recordar" value="1">
        Mantener la sesión iniciada
    </label>

    <button type="submit" class="btn btn-primario btn-bloque">Iniciar sesión</button>

    <p class="formulario-pie">
        ¿Primera vez en el hotel? <a href="{{ route('registro') }}">Crea tu cuenta</a>
    </p>
</form>
@endsection