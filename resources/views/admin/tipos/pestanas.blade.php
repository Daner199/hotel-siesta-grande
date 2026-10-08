{{-- Pestañas de un tipo: Tarifas | Fotos. Recibe $tipo y $activa ('tarifas' o 'fotos') --}}
<nav class="pestanas" aria-label="Secciones del tipo {{ $tipo->nombre }}">
    <a href="{{ route('admin.tipos.tarifas', $tipo) }}"
       @class(['pestana', 'activa' => $activa === 'tarifas'])
       @if ($activa === 'tarifas') aria-current="page" @endif>
        <i data-lucide="tags"></i> Tarifas
    </a>
    <a href="{{ route('admin.tipos.fotos', $tipo) }}"
       @class(['pestana', 'activa' => $activa === 'fotos'])
       @if ($activa === 'fotos') aria-current="page" @endif>
        <i data-lucide="images"></i> Fotos
    </a>
</nav>
