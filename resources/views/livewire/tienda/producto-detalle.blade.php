<div>
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('tienda.catalogo') }}">Catálogo</a></li>
            <li class="breadcrumb-item"><a href="{{ route('tienda.catalogo', ['categoria' => $producto->categoria->slug]) }}">{{ $producto->categoria->nombre }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $producto->nombre }}</li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-lg-4 row g-4">
            <div class="col-md-5">
                <img src="{{ $producto->imagen_url }}" alt="{{ $producto->nombre }}" class="img-fluid rounded-3 producto-imagen w-100">
            </div>

            <div class="col-md-7 d-flex flex-column">
                <div class="d-flex gap-2 mb-2">
                    <span class="badge bg-acento text-dark">{{ $producto->categoria->nombre }}</span>
                    <x-stock-badge :stock="$producto->stock" />
                </div>
                <h1 class="h2 mb-1">{{ $producto->nombre }}</h1>
                <p class="font-monospace small text-body-secondary">SKU {{ $producto->sku }}</p>
                <p class="lead">{{ $producto->descripcion_corta }}</p>

                <div class="mb-4">
                    <span class="display-6 fw-bold text-primary">${{ number_format($producto->precio_mxn, 2) }}</span>
                    <span class="text-body-secondary">MXN</span>
                    <div class="text-body-secondary">${{ number_format($producto->precio_usd, 2) }} USD</div>
                </div>

                @if ($producto->stock > 0)
                    <div class="d-flex flex-wrap align-items-center gap-3 mb-4" x-data="{ cantidad: 1, max: {{ $producto->stock }} }">
                        <div class="input-group" style="width: 140px">
                            <button type="button" class="btn btn-outline-secondary" aria-label="Menos"
                                    @click="cantidad = Math.max(1, cantidad - 1)" :disabled="cantidad <= 1">
                                <i class="bi bi-dash"></i>
                            </button>
                            <input type="text" class="form-control text-center" x-model.number="cantidad" readonly aria-label="Cantidad">
                            <button type="button" class="btn btn-outline-secondary" aria-label="Más"
                                    @click="cantidad = Math.min(max, cantidad + 1)" :disabled="cantidad >= max">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                        <button type="button" class="btn btn-primary btn-lg" @click="$wire.agregar(cantidad)"
                                wire:loading.attr="disabled" wire:target="agregar">
                            <i class="bi bi-bag-plus"></i> Agregar al carrito
                        </button>
                        <small class="text-body-secondary w-100" x-show="cantidad >= max" x-cloak>Máximo disponible: {{ $producto->stock }}</small>
                    </div>
                @else
                    <div class="alert alert-secondary">Producto agotado por ahora.</div>
                @endif

                <h2 class="h6 text-uppercase text-body-secondary">Descripción</h2>
                <p class="mb-0">{!! nl2br(e($producto->descripcion_larga)) !!}</p>
            </div>
        </div>
    </div>
</div>
