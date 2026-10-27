<div>
    <section class="hero rounded-4 p-4 p-lg-5 mb-4 text-white">
        <p class="text-acento text-uppercase small fw-semibold mb-1">Tienda demostrativa</p>
        <h1 class="display-6 mb-2">Bienestar para cada día</h1>
        <p class="mb-0 col-lg-7 text-white-50">Nutrición, bebidas y cuidado personal. Precios en pesos y dólares con el tipo de cambio del día.</p>
    </section>

    <div class="row g-4" x-data="{ filtros: false }">
        <aside class="col-lg-3">
            <button type="button" class="btn btn-outline-primary w-100 d-lg-none mb-2" @click="filtros = !filtros">
                <i class="bi bi-funnel"></i> Filtros
            </button>

            <div class="card border-0 shadow-sm d-lg-block" :class="{ 'd-none': !filtros }">
                <div class="card-body">
                    <label for="buscar" class="form-label fw-semibold">Buscar</label>
                    <input id="buscar" type="search" class="form-control mb-3" placeholder="Nombre o SKU"
                           wire:model.live.debounce.300ms="buscar">

                    <p class="form-label fw-semibold mb-2">Categoría</p>
                    <div class="list-group list-group-flush mb-3">
                        <button type="button" wire:click="$set('categoria', '')"
                                @class(['list-group-item list-group-item-action d-flex justify-content-between px-0 border-0', 'fw-semibold text-primary' => $categoria === ''])>
                            Todas
                        </button>
                        @foreach ($categorias as $item)
                            <button type="button" wire:key="categoria-{{ $item->id }}" wire:click="$set('categoria', '{{ $item->slug }}')"
                                    @class(['list-group-item list-group-item-action d-flex justify-content-between px-0 border-0', 'fw-semibold text-primary' => $categoria === $item->slug])>
                                {{ $item->nombre }}
                                <span class="badge rounded-pill bg-light text-body-secondary">{{ $item->productos_count }}</span>
                            </button>
                        @endforeach
                    </div>

                    <p class="form-label fw-semibold mb-2">Precio (MXN)</p>
                    <div class="row g-2 mb-3">
                        <div class="col">
                            <input type="number" min="0" class="form-control" placeholder="Mín." aria-label="Precio mínimo"
                                   wire:model.live.debounce.500ms="precioMin">
                        </div>
                        <div class="col">
                            <input type="number" min="0" class="form-control" placeholder="Máx." aria-label="Precio máximo"
                                   wire:model.live.debounce.500ms="precioMax">
                        </div>
                    </div>

                    <label for="disponibilidad" class="form-label fw-semibold">Disponibilidad</label>
                    <select id="disponibilidad" class="form-select mb-3" wire:model.live="disponibilidad">
                        <option value="">Todos</option>
                        <option value="disponibles">Con existencia</option>
                        <option value="agotados">Agotados</option>
                    </select>

                    <button type="button" class="btn btn-link btn-sm px-0" wire:click="limpiarFiltros">
                        <i class="bi bi-x-circle"></i> Limpiar filtros
                    </button>
                </div>
            </div>
        </aside>

        <section class="col-lg-9">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <p class="mb-0 text-body-secondary">
                    {{ $productos->total() }} {{ $productos->total() === 1 ? 'producto' : 'productos' }}
                    <span class="spinner-border spinner-border-sm text-primary ms-2" wire:loading.delay></span>
                </p>
                <select class="form-select w-auto" wire:model.live="orden" aria-label="Ordenar productos">
                    <option value="nombre">Nombre (A–Z)</option>
                    <option value="precio-asc">Precio: menor a mayor</option>
                    <option value="precio-desc">Precio: mayor a menor</option>
                </select>
            </div>

            <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 g-3" wire:loading.class="opacity-50">
                @forelse ($productos as $producto)
                    <div class="col" wire:key="producto-{{ $producto->id }}">
                        <article class="card h-100 border-0 shadow-sm producto-card">
                            <a href="{{ route('tienda.producto', $producto->sku) }}">
                                <img src="{{ $producto->imagen_url }}" alt="{{ $producto->nombre }}" class="card-img-top producto-imagen" loading="lazy">
                            </a>
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <span class="badge bg-acento text-dark">{{ $producto->categoria->nombre }}</span>
                                    <x-stock-badge :stock="$producto->stock" />
                                </div>
                                <h2 class="h6 mb-1">
                                    <a href="{{ route('tienda.producto', $producto->sku) }}" class="text-reset text-decoration-none">{{ $producto->nombre }}</a>
                                </h2>
                                <p class="small text-body-secondary flex-grow-1">{{ $producto->descripcion_corta }}</p>
                                <div class="d-flex align-items-end justify-content-between gap-2">
                                    <div>
                                        <div class="fs-5 fw-bold text-primary">${{ number_format($producto->precio_mxn, 2) }} <small class="fs-6 fw-normal">MXN</small></div>
                                        <div class="small text-body-secondary">${{ number_format($producto->precio_usd, 2) }} USD</div>
                                    </div>
                                    <button type="button" class="btn btn-primary" title="Agregar al carrito"
                                            wire:click="agregar({{ $producto->id }})" wire:loading.attr="disabled" wire:target="agregar({{ $producto->id }})"
                                            @disabled($producto->stock === 0)>
                                        <i class="bi bi-bag-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </article>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="text-center text-body-secondary py-5">
                            <i class="bi bi-search fs-1 d-block mb-2"></i>
                            No encontramos productos con esos filtros.
                            <button type="button" class="btn btn-link" wire:click="limpiarFiltros">Limpiar filtros</button>
                        </div>
                    </div>
                @endforelse
            </div>

            @if ($productos->hasPages())
                <div class="mt-4">{{ $productos->links() }}</div>
            @endif
        </section>
    </div>
</div>
