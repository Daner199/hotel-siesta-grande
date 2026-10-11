<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $recibo->codigo() }}</title>
    <style>
        @page { margin: 36px 42px; }
        body { margin: 0; font-family: "DejaVu Sans", sans-serif; }
    </style>
</head>
<body>
    @include('recibos.documento')
</body>
</html>
