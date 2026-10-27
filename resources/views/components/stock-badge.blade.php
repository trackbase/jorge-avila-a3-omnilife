@props(['stock'])

@if ($stock === 0)
    <span {{ $attributes->merge(['class' => 'badge text-bg-secondary']) }}>Agotado</span>
@elseif ($stock <= 5)
    <span {{ $attributes->merge(['class' => 'badge text-bg-warning']) }}>Últimas {{ $stock }}</span>
@else
    <span {{ $attributes->merge(['class' => 'badge bg-success-subtle text-success-emphasis']) }}>Disponible</span>
@endif
