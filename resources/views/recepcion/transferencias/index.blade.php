@extends('layouts.panel')

{{-- Transferencias: por revisar (las más antiguas primero), aprobadas y rechazadas --}}
@use('App\Models\Pago')
@use('App\Support\Moneda')

@section('titulo', 'Transferencias')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/pagos.css') }}">
@endpush

@section('panel')
<header class="cabecera-pagina">
    <div>
        <nav class="migas" aria-label="Ruta">
            <a href="{{ route('recepcion.inicio') }}">Inicio</a>
            <i data-lucide="chevron-right"></i>
            <span>Transferencias</span>
        </nav>
        <h1>Transferencias</h1>
    </div>
</header>

<section class="caja-plana">
    <nav class="pestanas-planas" aria-label="Estado">
        <a href="{{ route('recepcion.transferencias.index') }}" @class(['activa' => $estado === Pago::PENDIENTE])>
            Por revisar @if ($pendientes) <span class="contador-menu">{{ $pendientes }}</span> @endif
        </a>
        <a href="{{ route('recepcion.transferencias.index', ['estado' => Pago::APROBADO]) }}" @class(['activa' => $estado === Pago::APROBADO])>Aprobadas</a>
        <a href="{{ route('recepcion.transferencias.index', ['estado' => Pago::RECHAZADO]) }}" @class(['activa' => $estado === Pago::RECHAZADO])>Rechazadas</a>
    </nav>

    @if ($pagos->isEmpty())
        <p class="vacio-plano">
            {{ $estado === Pago::PENDIENTE ? 'No hay transferencias por revisar.' : 'No hay transferencias en esta lista.' }}
        </p>
    @else
        <div class="tabla-envoltura">
            <table class="tabla tabla-compacta">
                <thead>
                    <tr>
                        <th>Enviada</th>
                        <th>Reserva</th>
                        <th>Nro. de operación</th>
                        <th>Monto</th>
                        @if ($estado !== Pago::PENDIENTE) <th>Revisada por</th> @endif
                        <th><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pagos as $pago)
                        <tr>
                            <td>
                                {{ $pago->fecha_pago->format('d/m/Y H:i') }}
                                <span class="dato-secundario">{{ $pago->fecha_pago->locale('es')->diffForHumans() }}</span>
                            </td>
                            <td>
                                <strong>{{ $pago->reserva->codigo }}</strong>
                                <span class="dato-secundario">{{ $pago->reserva->nombre_titular }}</span>
                            </td>
                            <td><code class="referencia">{{ $pago->transferencia->numero_operacion }}</code></td>
                            <td><span class="precio">{{ Moneda::formato($pago->monto) }}</span></td>
                            @if ($estado !== Pago::PENDIENTE)
                                <td>
                                    {{ $pago->transferencia->revisor?->nombreCompleto() }}
                                    <span class="dato-secundario">{{ $pago->transferencia->fecha_revision?->format('d/m/Y H:i') }}</span>
                                </td>
                            @endif
                            <td>
                                <div class="acciones">
                                    <a href="{{ route('recepcion.transferencias.show', $pago) }}" class="boton-accion">
                                        <span>{{ $estado === Pago::PENDIENTE ? 'Revisar' : 'Ver' }}</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="paginacion">
            {{ $pagos->links('pagination::default') }}
        </div>
    @endif
</section>
@endsection
