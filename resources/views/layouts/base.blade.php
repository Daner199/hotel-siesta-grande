<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo') · Hotel Siesta Grande</title>
    @stack('meta')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:opsz,wght@6..96,400;6..96,500&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/hotel.css') }}">
    @stack('estilos')
</head>
<body>
    @yield('contenido')

    {{-- Íconos --}}
    <script src="https://unpkg.com/lucide@0.469.0/dist/umd/lucide.min.js"></script>
    <script>window.lucide && lucide.createIcons();</script>

    {{-- Ojito en todas las contraseñas --}}
    <script src="{{ asset('js/contrasena.js') }}"></script>

    @stack('scripts')
</body>
</html>