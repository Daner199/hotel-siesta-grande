{{-- Reporte en PDF (dompdf): tablas y CSS simple, sin flex ni grid --}}
@use('App\Support\Graficos')
@use('App\Support\Reportes')
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $reporte['titulo'] }}</title>
    <style>
        @page { margin: 34px 38px 44px; }
        body { margin: 0; font-family: "DejaVu Sans", sans-serif; font-size: 10px; line-height: 1.4; color: #17221C; }
        table { width: 100%; border-collapse: collapse; }
        .cabecera td { vertical-align: middle; padding-bottom: 10px; border-bottom: 2px solid #1D3A2E; }
        .logo { width: 40px; height: 40px; }
        .hotel { font-size: 13px; font-weight: bold; color: #1D3A2E; }
        .gris { color: #5D6A63; }
        h1 { margin: 14px 0 2px; font-size: 16px; }
        .periodo { margin: 0 0 12px; color: #5D6A63; }
        .cifras { table-layout: fixed; }
        .cifras td { padding: 6px 10px 6px 0; border-bottom: 1px solid #E3E6E1; }
        .cifras .valor { font-size: 13px; font-weight: bold; }
        h2 { margin: 16px 0 6px; font-size: 11.5px; }
        .datos th { padding: 5px 6px; text-align: left; font-size: 9px; color: #fff; background: #1D3A2E; }
        .datos td { padding: 4px 6px; border-bottom: 1px solid #E3E6E1; }
        .datos .num { text-align: right; }
        .datos tfoot td { font-weight: bold; border-top: 1.5px solid #1D3A2E; border-bottom: 0; }
        .vacio { color: #5D6A63; }
        .grafico { margin: 6px 0 4px; }
        .grafico img { width: 100%; }
        .nota { margin-top: 14px; color: #5D6A63; font-style: italic; }
        .pie { position: fixed; bottom: -28px; left: 0; right: 0; font-size: 8.5px; color: #5D6A63; }
    </style>
</head>
<body>
    <div class="pie">{{ $hotel['nombre'] }} · {{ $reporte['titulo'] }} · Generado el {{ now()->format('d/m/Y H:i') }}</div>

    <table class="cabecera">
        <tr>
            @if ($logo)
                <td style="width: 48px;"><img src="{{ $logo }}" class="logo" alt=""></td>
            @endif
            <td>
                <div class="hotel">{{ $hotel['nombre'] }}</div>
                <div class="gris">{{ $hotel['direccion']['calle'] }}, {{ $hotel['direccion']['ciudad'] }}</div>
            </td>
            <td style="text-align: right;" class="gris">Reporte<br>{{ now()->format('d/m/Y H:i') }}</td>
        </tr>
    </table>

    <h1>{{ $reporte['titulo'] }}</h1>
    <p class="periodo">{{ $reporte['periodo'] }}</p>

    @if (! empty($reporte['cifras']))
        <table class="cifras">
            <tr>
                @foreach ($reporte['cifras'] as $cifra)
                    <td>
                        <div class="gris">{{ $cifra['texto'] }}</div>
                        <div class="valor">{{ $cifra['valor'] }}</div>
                    </td>
                @endforeach
            </tr>
        </table>
    @endif

    @foreach ($reporte['graficos'] ?? [] as $grafico)
        <h2>{{ $grafico['titulo'] }}</h2>
        <div class="grafico"><img src="{{ Graficos::dataUri($grafico['svg']) }}" alt=""></div>
    @endforeach

    @foreach ($reporte['tablas'] as $tabla)
        <h2>{{ $tabla['titulo'] }}</h2>
        @if (empty($tabla['filas']))
            <p class="vacio">{{ $tabla['vacio'] }}</p>
        @else
            <table class="datos">
                <thead>
                    <tr>
                        @foreach ($tabla['columnas'] as $col)
                            <th @class(['num' => Reportes::esNumero($col['tipo'])])>{{ $col['texto'] }}</th>
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
        @endif
    @endforeach

    @if (! empty($reporte['nota']))
        <p class="nota">{{ $reporte['nota'] }}</p>
    @endif
</body>
</html>
