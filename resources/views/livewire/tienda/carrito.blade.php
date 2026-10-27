<div>
    <h1 class="h3 mb-3">Tu carrito</h1>

    @if ($lineas->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-bag fs-1 text-body-tertiary d-block mb-2"></i>
                <p class="text-body-secondary">Tu carrito está vacío.</p>
                <a href="{{ route('tienda.catalogo') }}" class="btn btn-primary">Ver catálogo</a>
            </div>
        </div>
    @else
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <ul class="list-group list-group-flush" wire:loading.class="opacity-75">
                        @foreach ($lineas as $linea)
                            @php($producto = $linea['producto'])
                            {{-- La llave incluye la cantidad: si el servidor la corrige, Alpine se reinicia con el valor real. --}}
                            <li class="list-group-item py-3" wire:key="linea-{{ $producto->id }}-{{ $linea['cantidad'] }}">
                                <div class="d-flex gap-3 align-items-center">
                                    <img src="{{ $producto->imagen_url }}" alt="{{ $producto->nombre }}" class="rounded producto-imagen" width="72" height="72">
                                    <div class="flex-grow-1">
                                        <a href="{{ route('tienda.producto', $producto->sku) }}" class="fw-semibold text-reset text-decoration-none">{{ $producto->nombre }}</a>
                                        <div class="small text-body-secondary">
                                            ${{ number_format($producto->precio_mxn, 2) }} MXN · ${{ number_format($producto->precio_usd, 2) }} USD c/u
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-semibold">${{ number_format($linea['subtotal_mxn'], 2) }}</div>
                                        <div class="small text-body-secondary">${{ number_format($linea['subtotal_usd'], 2) }} USD</div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2 ms-lg-5 ps-lg-4"
                                     x-data="{ cantidad: {{ $linea['cantidad'] }}, max: {{ $producto->stock }} }">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="input-group input-group-sm" style="width: 120px">
                                            <button type="button" class="btn btn-outline-secondary" aria-label="Menos"
                                                    @click="cantidad--; $wire.actualizar({{ $producto->id }}, cantidad)" :disabled="cantidad <= 1">
                                                <i class="bi bi-dash"></i>
                                            </button>
                                            <input type="text" class="form-control text-center" x-model.number="cantidad" readonly aria-label="Cantidad">
                                            <button type="button" class="btn btn-outline-secondary" aria-label="Más"
                                                    @click="cantidad++; $wire.actualizar({{ $producto->id }}, cantidad)" :disabled="cantidad >= max">
                                                <i class="bi bi-plus"></i>
                                            </button>
                                        </div>
                                        <small class="text-body-secondary" x-show="cantidad >= max" x-cloak>Máximo disponible</small>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-link text-danger" wire:click="quitar({{ $producto->id }})">
                                        <i class="bi bi-trash"></i> Quitar
                                    </button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h5 mb-3">Resumen</h2>
                        <x-resumen-totales :$totales />
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
