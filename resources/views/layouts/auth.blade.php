@extends('layouts.base')

@section('contenido')
<div class="auth">
    <aside class="auth-lado">
        <a href="{{ url('/') }}" class="marca">Siesta Grande</a>
        <p class="auth-frase">@yield('frase')</p>
        <p class="auth-pie">Santa Cruz de la Sierra, Bolivia</p>
    </aside>

    <main class="auth-contenido">
        @yield('formulario')
    </main>
</div>
@endsection