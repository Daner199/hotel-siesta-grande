@extends('layouts.panel')

{{-- Comisiones del recepcionista: Bs 5 por cada reserva que confirmó --}}
@use('App\Models\ComisionRecepcionista')
@use('App\Support\Moneda')

@section('titulo', 'Mis comisiones')

@section('panel')
<header class="cabecera-pagina">
    <div>
        <nav class="migas" aria-label="Ruta">
            <a href="{{ route('recepcion.inicio') }}">Inicio</a>
            <i data-lucide="chevron-right"></i>
            <span>Mis comisiones</span>
        </nav>
        <h1>Mis comisiones</h1>
        <p>Ganas {{ Moneda::formato(ComisionRecepcionista::MONTO) }} por cada reserva que confirmas al registrar su adelanto.</p>
    </div>
</header>

<section class="estadisticas">
    <div class="estadistica estadistica-rosa">
        <span class="estadistica-icono"><i data-lucide="calendar-check"></i></span>
        <div>
            <p class="estadistica-valor">{{ $totales->cantidad }}</p>
            <p class="estadistica-texto">{{ (int) $totales->cantidad === 1 ? 'Reserva confirmada' : 'Reservas confirmadas' }}</p>
        </div>
    </div>
    <div class="estadistica estadistica-verde">
        <span class="estadistica-icono"><i data-lucide="coins"></i></span>
        <div>
            <p class="estadistica-valor estadistica-monto">{{ Moneda::formato($totales->mes) }}</p>
            <p class="estadistica-texto">Este mes</p>
        </div>
    </div>
    <div class="estadistica estadistica-dorado">
        <span class="estadistica-icono"><i data-lucide="hand-coins"></i></span>
        <div>
            <p class="estadistica-valor estadistica-monto">{{ Moneda::formato($totales->por_cobrar) }}</p>
            <p class="estadistica-texto">Por cobrar</p>
        </div>
    </div>
    <div class="estadistica estadistica-azul">
        <span class="estadistica-icono"><i data-lucide="wallet"></i></span>
        <div>
            <p class="estadistica-valor estadistica-monto">{{ Moneda::formato($totales->total) }}</p>
            <p class="estadistica-texto">Total ganado</p>
        </div>
    </div>
</section>

<section class="bloque">
    @if ($comisiones->isEmpty())
        <div class="vacio-panel">
            <span class="vacio-icono"><i data-lucide="coins"></i></span>
            <h3>Todavía no tienes comisiones</h3>
            <p>Cuando registres el adelanto de una reserva pendiente, se confirma y ganas tu comisión.</p>
            <a href="{{ route('recepcion.reservas.index') }}" class="btn btn-secundario">Ver reservas</a>
        </div>
    @else
        <div class="tabla-envoltura">
            <table class="tabla tabla-compacta">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Reserva</th>
                        <th>Comisión</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($comisiones as $c)
                        <tr>
                            <td>{{ $c->fecha_generacion->format('d/m/Y H:i') }}</td>
                            <td>
                                <a href="{{ route('recepcion.reservas.show', $c->reserva_id) }}" class="enlace-reserva">{{ $c->reserva->codigo }}</a>
                                <span class="dato-secundario">{{ $c->reserva->nombre_titular }}</span>
                            </td>
                            <td><span class="precio">{{ Moneda::formato($c->monto) }}</span></td>
                            <td>
                                <span @class(['estado', 'estado-pago-aprobado' => $c->pagada, 'estado-pago-devuelto' => ! $c->pagada])>
                                    {{ $c->pagada ? 'Pagada' : 'Por cobrar' }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="paginacion">
            {{ $comisiones->links('pagination::default') }}
        </div>
    @endif
</section>
@endsection
