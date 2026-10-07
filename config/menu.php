<?php

/*
| Menú lateral de cada rol. Cada bloque es el menú que ve ESE rol.
| 'ruta'   => nombre de la ruta de Laravel, o null si el módulo aún no existe.
| 'patron' => (opcional) rutas en las que el enlace se marca como activo.
| 'icono'  => nombre del ícono en https://lucide.dev/icons
*/

return [

    // ===== Menú que ve el ADMINISTRADOR =====
    'ADMINISTRADOR' => [
        ['grupo' => 'General', 'items' => [
            ['texto' => 'Inicio', 'icono' => 'layout-dashboard', 'ruta' => 'admin.inicio'],
        ]],
        ['grupo' => 'Personas', 'items' => [
            ['texto' => 'Recepcionistas', 'icono' => 'concierge-bell', 'ruta' => 'admin.recepcionistas.index', 'patron' => 'admin.recepcionistas.*'],
            ['texto' => 'Agencias', 'icono' => 'building-2', 'ruta' => null],
        ]],
        ['grupo' => 'Hotel', 'items' => [
            ['texto' => 'Habitaciones', 'icono' => 'bed-double', 'ruta' => null],
            ['texto' => 'Tarifas', 'icono' => 'tags', 'ruta' => null],
            ['texto' => 'Promociones', 'icono' => 'sparkles', 'ruta' => null],
            ['texto' => 'Beneficios', 'icono' => 'gift', 'ruta' => null],
            ['texto' => 'Salón de eventos', 'icono' => 'party-popper', 'ruta' => null],
        ]],
        ['grupo' => 'Análisis', 'items' => [
            ['texto' => 'Reportes', 'icono' => 'chart-column', 'ruta' => null],
        ]],
    ],

    // ===== Menú que ve el RECEPCIONISTA =====
    'RECEPCIONISTA' => [
        ['grupo' => 'General', 'items' => [
            ['texto' => 'Inicio', 'icono' => 'layout-dashboard', 'ruta' => 'recepcion.inicio'],
        ]],
        ['grupo' => 'Operación', 'items' => [
            ['texto' => 'Reservas', 'icono' => 'calendar-check', 'ruta' => null],
            ['texto' => 'Check-in / Check-out', 'icono' => 'door-open', 'ruta' => null],
            ['texto' => 'Pagos', 'icono' => 'wallet', 'ruta' => null],
            ['texto' => 'Consumos', 'icono' => 'utensils', 'ruta' => null],
            ['texto' => 'Huéspedes alojados', 'icono' => 'users', 'ruta' => null],
        ]],
    ],

    // ===== Menú que ve la AGENCIA =====
    'AGENCIA' => [
        ['grupo' => 'General', 'items' => [
            ['texto' => 'Inicio', 'icono' => 'layout-dashboard', 'ruta' => 'agencia.inicio'],
        ]],
        ['grupo' => 'Reservas', 'items' => [
            ['texto' => 'Nueva reserva', 'icono' => 'calendar-plus', 'ruta' => null],
            ['texto' => 'Mis reservas', 'icono' => 'calendar-check', 'ruta' => null],
            ['texto' => 'Pagos', 'icono' => 'wallet', 'ruta' => null],
        ]],
    ],

    // ===== Menú que ve el CLIENTE =====
    'CLIENTE' => [
        ['grupo' => 'General', 'items' => [
            ['texto' => 'Inicio', 'icono' => 'layout-dashboard', 'ruta' => 'cliente.inicio'],
        ]],
        ['grupo' => 'Mi estadía', 'items' => [
            ['texto' => 'Nueva reserva', 'icono' => 'calendar-plus', 'ruta' => null],
            ['texto' => 'Mis reservas', 'icono' => 'calendar-check', 'ruta' => null],
            ['texto' => 'Mis pagos', 'icono' => 'wallet', 'ruta' => null],
        ]],
    ],

];