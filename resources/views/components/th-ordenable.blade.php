@props(['columna', 'orden', 'direccion'])

<th {{ $attributes->merge(['scope' => 'col']) }}>
    <button type="button" class="btn btn-link p-0 text-reset text-decoration-none fw-semibold text-nowrap" wire:click="ordenarPor('{{ $columna }}')">
        {{ $slot }}
        @if ($orden === $columna)
            <i class="bi bi-caret-{{ $direccion === 'asc' ? 'up' : 'down' }}-fill text-primary"></i>
        @else
            <i class="bi bi-chevron-expand text-body-tertiary"></i>
        @endif
    </button>
</th>
