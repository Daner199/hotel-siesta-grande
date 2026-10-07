@extends('layouts.base')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/panel.js') }}"></script>
@endpush

@php
    $usuario = auth()->user();
    $menu = config('menu.' . $usuario->rol, []);

    $nombresRol = [
        'ADMINISTRADOR' => 'Administración',
        'RECEPCIONISTA' => 'Recepción',
        'AGENCIA'       => 'Agencia',
        'CLIENTE'       => 'Cliente',
    ];

    $iniciales = mb_strtoupper(
        mb_substr($usuario->nombre, 0, 1) . mb_substr($usuario->apellido ?? '', 0, 1)
    );
@endphp

@section('contenido')
<div class="app">

    {{-- ===== MENÚ LATERAL ===== --}}
    <aside class="lateral" id="lateral" aria-label="Menú principal">
        <div class="lateral-marca">
            <span class="monograma" aria-hidden="true">SG</span>
            <div>
                <strong>Siesta Grande</strong>
                <span>{{ $nombresRol[$usuario->rol] }}</span>
            </div>
        </div>

        <nav class="lateral-nav">
            @foreach ($menu as $bloque)
                <p class="lateral-grupo">{{ $bloque['grupo'] }}</p>
                <ul>
                    @foreach ($bloque['items'] as $item)
                        <li>
                            @if ($item['ruta'])
                                @php $activo = request()->routeIs($item['patron'] ?? $item['ruta']); @endphp
                                <a href="{{ route($item['ruta']) }}"
                                   @class(['lateral-enlace', 'activo' => $activo])
                                   @if ($activo) aria-current="page" @endif>
                                    <i data-lucide="{{ $item['icono'] }}"></i>
                                    <span>{{ $item['texto'] }}</span>
                                </a>
                            @else
                                <span class="lateral-enlace deshabilitado" title="Disponible en un próximo módulo">
                                    <i data-lucide="{{ $item['icono'] }}"></i>
                                    <span>{{ $item['texto'] }}</span>
                                    <small>Pronto</small>
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </nav>

        <div class="lateral-usuario">
            <span class="avatar">{{ $iniciales }}</span>
            <div class="lateral-usuario-datos">
                <strong>{{ $usuario->nombreCompleto() }}</strong>
                <span>{{ $usuario->email }}</span>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="boton-icono" title="Cerrar sesión" aria-label="Cerrar sesión">
                    <i data-lucide="log-out"></i>
                </button>
            </form>
        </div>
    </aside>

    {{-- Fondo oscuro detrás del menú en celular --}}
    <div class="velo" data-cerrar-menu hidden></div>

    {{-- ===== CONTENIDO ===== --}}
    <div class="principal">
        <header class="barra">
            <button type="button" class="boton-icono boton-menu" data-abrir-menu
                    aria-label="Abrir menú" aria-controls="lateral">
                <i data-lucide="menu"></i>
            </button>

            <div class="barra-titulo">
                <span class="barra-fecha">
                    {{ ucfirst(now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY')) }}
                </span>
                <strong>@yield('titulo')</strong>
            </div>

            <div class="barra-acciones">
                <a href="{{ route('inicio') }}" class="boton-suave" target="_blank" rel="noopener">
                    <i data-lucide="globe"></i>
                    <span>Ver sitio</span>
                </a>
                <span class="avatar avatar-chico" aria-hidden="true">{{ $iniciales }}</span>
            </div>
        </header>

        <main class="contenido">
            @yield('panel')
        </main>
    </div>
</div>

{{-- ===== AVISO FLOTANTE (éxito o error) ===== --}}
@if (session('exito') || session('error'))
    <div @class(['aviso', 'aviso-error' => session('error')]) role="status" data-aviso>
        <i data-lucide="{{ session('error') ? 'circle-alert' : 'circle-check' }}"></i>
        <span>{{ session('exito') ?? session('error') }}</span>
        <button type="button" class="boton-icono" data-cerrar-aviso aria-label="Cerrar aviso">
            <i data-lucide="x"></i>
        </button>
    </div>
@endif
@endsection