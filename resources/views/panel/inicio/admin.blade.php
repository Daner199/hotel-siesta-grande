{{-- Inicio del administrador: ocupación de esta noche por tipo, próximas llegadas y reportes --}}
@use('App\Support\Moneda')

<div class="inicio-rejilla">
    <section class="caja-plana">
        <div class="caja-cabecera">
            <h2>Ocupación esta noche</h2>
            <a href="{{ route('admin.reportes.show', 'ocupacion') }}">Ocupación del mes</a>
        </div>
        <ul class="barras-inicio">
            @foreach ($porTipo as $t)
                @php $pct = $t['ocupadas'] / $t['enServicio'] * 100; @endphp
                <li>
                    <span>{{ $t['tipo'] }}</span>
                    <span class="barra-progreso" role="img" aria-label="{{ round($pct) }} %"><span style="width: {{ $pct }}%"></span></span>
                    <strong>{{ $t['ocupadas'] }} de {{ $t['enServicio'] }}</strong>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="caja-plana">
        <div class="caja-cabecera">
            <h2>Reportes</h2>
        </div>
        <ul class="lista-enlaces">
            @foreach ($reportes as $clave => $nombre)
                <li><a href="{{ route('admin.reportes.show', $clave) }}">{{ $nombre }} <i data-lucide="chevron-right"></i></a></li>
            @endforeach
        </ul>
    </section>
</div>

<section class="caja-plana caja-inicio">
    <div class="caja-cabecera">
        <h2>Próximas llegadas</h2>
        <span class="caja-nota">Próximos 7 días</span>
    </div>
    @if ($proximas->isEmpty())
        <p class="vacio-plano">Sin llegadas en los próximos 7 días.</p>
    @else
        <div class="tabla-envoltura">
            <table class="tabla tabla-compacta tabla-inicio">
                <thead>
                    <tr>
                        <th>Llegada</th>
                        <th>Titular</th>
                        <th>Canal</th>
                        <th class="num">Hab.</th>
                        <th class="num">Importe</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($proximas as $r)
                        <tr>
                            <td>{{ $r->fecha_entrada->format('d/m') }} <span class="dato-gris">· {{ $r->noches() }} n.</span></td>
                            <td><strong>{{ $r->nombre_titular }}</strong> <span class="dato-gris">{{ $r->codigo }}</span></td>
                            <td>{{ $r->agencia?->nombre ?? 'Directo' }}</td>
                            <td class="num">{{ $r->habitaciones->count() }}</td>
                            <td class="num">{{ Moneda::formato($r->total()) }}</td>
                            <td><span class="estado estado-reserva estado-reserva-{{ strtolower($r->estado->nombre) }}">{{ $r->estado->etiqueta() }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
