@extends('layouts.panel')

{{-- Reportes (Módulo 9): pestañas, filtros, PDF/Excel, cifras, gráfico y tablas --}}
@use('App\Support\Reportes')

@section('titulo', 'Reportes')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/pagos.css') }}">
    <link rel="stylesheet" href="{{ asset('css/reportes.css') }}">
@endpush

@php
    $clave  = $reporte['clave'];
    $ruta   = $prefijo . '.reportes.show';
    $filtros = array_filter($reporte['filtros'], fn ($v) => $v !== null && $v !== '');
@endphp

@section('panel')
<header class="cabecera-pagina">
    <div>
        <nav class="migas" aria-label="Ruta">
            <a href="{{ route($prefijo . '.inicio') }}">Inicio</a>
            <i data-lucide="chevron-right"></i>
            <span>Reportes</span>
        </nav>
        <h1>Reportes</h1>
    </div>
</header>

<nav class="pestanas-planas pestanas-reportes" aria-label="Reportes">
    @foreach ($permitidos as $c => $titulo)
        <a href="{{ route($ruta, $c) }}" @class(['activa' => $c === $clave]) @if ($c === $clave) aria-current="page" @endif>{{ $titulo }}</a>
    @endforeach
</nav>

<section class="caja-plana">
    <form method="GET" action="{{ route($ruta, $clave) }}" class="filtros-reporte">
        @include('reportes.filtros')
    </form>
</section>

<section class="caja-plana hoja-reporte">
    <div class="reporte-cabecera">
        <div>
            <h2>{{ $reporte['titulo'] }}</h2>
            <p>{{ $reporte['periodo'] }}</p>
        </div>
        @if ($reporte['exportable'] ?? true)
            <div class="reporte-exportar">
                <a href="{{ route($ruta, [$clave] + $filtros + ['exportar' => 'pdf']) }}" class="boton-accion">
                    <i data-lucide="file-text"></i> PDF
                </a>
                <a href="{{ route($ruta, [$clave] + $filtros + ['exportar' => 'excel']) }}" class="boton-accion">
                    <i data-lucide="sheet"></i> Excel
                </a>
            </div>
        @endif
    </div>

    @if (! empty($reporte['cifras']))
        <dl class="cifras-reporte">
            @foreach ($reporte['cifras'] as $cifra)
                <div>
                    <dt>{{ $cifra['texto'] }}</dt>
                    <dd>{{ $cifra['valor'] }}</dd>
                </div>
            @endforeach
        </dl>
    @endif

    @if (! empty($reporte['graficos']))
        <div class="graficos-reporte">
            @foreach ($reporte['graficos'] as $grafico)
                <figure class="grafico">
                    <figcaption>{{ $grafico['titulo'] }}</figcaption>
                    {!! $grafico['svg'] !!}
                </figure>
            @endforeach
        </div>
    @endif

    @foreach ($reporte['tablas'] as $tabla)
        <h3 class="tabla-reporte-titulo">{{ $tabla['titulo'] }}</h3>
        @if (empty($tabla['filas']))
            <p class="vacio-plano">{{ $tabla['vacio'] }}</p>
        @else
            <div class="tabla-envoltura">
                <table class="tabla tabla-compacta tabla-reporte">
                    <thead>
                        <tr>
                            @foreach ($tabla['columnas'] as $col)
                                <th @class(['num' => Reportes::esNumero($col['tipo'])]) scope="col">{{ $col['texto'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tabla['filas'] as $fila)
                            <tr>
                                @foreach ($tabla['columnas'] as $i => $col)
                                    <td @class(['num' => Reportes::esNumero($col['tipo'])])>{{ Reportes::celda($fila[$i] ?? null, $col['tipo']) }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                    @if (! empty($tabla['pie']))
                        <tfoot>
                            <tr>
                                @foreach ($tabla['columnas'] as $i => $col)
                                    <td @class(['num' => Reportes::esNumero($col['tipo'])])>
                                        {{ $i === 0 ? $tabla['pie'][0] : (($tabla['pie'][$i] ?? null) === null ? '' : Reportes::celda($tabla['pie'][$i], $col['tipo'])) }}
                                    </td>
                                @endforeach
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        @endif
    @endforeach

    @if (! empty($reporte['nota']))
        <p class="nota-reporte">{{ $reporte['nota'] }}</p>
    @endif
</section>
@endsection
