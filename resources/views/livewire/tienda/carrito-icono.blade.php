<a href="{{ route('tienda.carrito') }}" class="nav-link position-relative" aria-label="Carrito con {{ $cantidad }} productos">
    <i class="bi bi-bag fs-5"></i>
    <span class="d-lg-none ms-1">Carrito</span>
    @if ($cantidad > 0)
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-acento text-dark">{{ $cantidad }}</span>
    @endif
</a>
