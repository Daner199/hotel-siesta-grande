@extends('layouts.panel')

@section('titulo', "Fotos · {$tipo->nombre}")

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
        <h1>Fotos de {{ $tipo->nombre }}</h1>
        <p>Se muestran en la página pública del hotel y al reservar habitaciones de este tipo.</p>
    </div>

    <a href="{{ route('admin.tipos.edit', $tipo) }}" class="btn btn-secundario">
        <i data-lucide="pencil"></i>
        Editar tipo
    </a>
</header>

@include('admin.tipos.pestanas', ['activa' => 'fotos'])

@include('admin.fotos.galeria', [
    'fotos'   => $fotos,
    'prefijo' => 'admin.tipos.fotos',
    'padre'   => $tipo,
    'nombre'  => $tipo->nombre,
])
@endsection
