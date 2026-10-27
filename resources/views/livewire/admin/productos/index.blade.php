<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0">Productos</h1>
            <p class="text-body-secondary small mb-0">
                {{ $productos->total() }} {{ $eliminados ? 'productos eliminados' : 'productos en el catálogo (sin eliminados)' }}.
            </p>
        </div>
        <a href="{{ route('admin.productos.crear') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nuevo</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body border-bottom d-flex align-items-center gap-2">
            <div class="input-group" style="max-width: 420px">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="search" class="form-control" placeholder="Buscar por SKU o nombre"
                       wire:model.live.debounce.300ms="buscar" aria-label="Buscar productos">
            </div>
            <div class="spinner-border spinner-border-sm text-primary" role="status" wire:loading.delay wire:target="buscar"></div>
            <div class="form-check form-switch ms-auto mb-0">
                <input id="eliminados" type="checkbox" role="switch" class="form-check-input" wire:model.live="eliminados">
                <label for="eliminados" class="form-check-label small">Ver eliminados</label>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <x-th-ordenable columna="sku" :$orden :$direccion>SKU</x-th-ordenable>
                        <x-th-ordenable columna="nombre" :$orden :$direccion>Nombre</x-th-ordenable>
                        <x-th-ordenable columna="precio_usd" :$orden :$direccion class="text-end">Precio USD</x-th-ordenable>
                        <x-th-ordenable columna="precio_mxn" :$orden :$direccion class="text-end">Precio MXN</x-th-ordenable>
                        <x-th-ordenable columna="stock" :$orden :$direccion class="text-center">Stock</x-th-ordenable>
                        <x-th-ordenable columna="activo" :$orden :$direccion class="text-center">Activo</x-th-ordenable>
                        <th scope="col" class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody wire:loading.class="opacity-50">
                    @forelse ($productos as $producto)
                        <tr wire:key="producto-{{ $producto->id }}">
                            <td class="font-monospace small">{{ $producto->sku }}</td>
                            <td>{{ $producto->nombre }}</td>
                            <td class="text-end">${{ number_format($producto->precio_usd, 2) }}</td>
                            <td class="text-end">${{ number_format($producto->precio_mxn, 2) }}</td>
                            <td class="text-center">
                                @if ($producto->stock === 0)
                                    <span class="badge text-bg-danger">Agotado</span>
                                @elseif ($producto->stock <= 5)
                                    <span class="badge text-bg-warning">{{ $producto->stock }} · bajo</span>
                                @else
                                    <span class="badge bg-success-subtle text-success-emphasis">{{ $producto->stock }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span @class(['badge', 'text-bg-success' => $producto->activo, 'text-bg-secondary' => ! $producto->activo])>
                                    {{ $producto->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                @if ($producto->trashed())
                                    <button type="button" class="btn btn-sm btn-outline-success" wire:click="restaurar({{ $producto->id }})">
                                        <i class="bi bi-arrow-counterclockwise"></i> Restaurar
                                    </button>
                                @else
                                    <a href="{{ route('admin.productos.detalle', $producto) }}" class="btn btn-sm btn-outline-secondary" title="Detalle"><i class="bi bi-eye"></i></a>
                                    <a href="{{ route('admin.productos.editar', $producto) }}" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                                    <button type="button" class="btn btn-sm btn-outline-{{ $producto->activo ? 'warning' : 'success' }}"
                                            wire:click="alternarActivo({{ $producto->id }})" title="{{ $producto->activo ? 'Inactivar' : 'Activar' }}">
                                        <i class="bi bi-toggle-{{ $producto->activo ? 'on' : 'off' }}"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" title="Eliminar"
                                            wire:click="eliminar({{ $producto->id }})"
                                            wire:confirm="¿Eliminar {{ $producto->sku }} · {{ $producto->nombre }}? Dejará de mostrarse en la tienda.">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-body-secondary py-5">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                @if ($buscar !== '')
                                    No hay productos que coincidan con “{{ $buscar }}”.
                                @elseif ($eliminados)
                                    No hay productos eliminados.
                                @else
                                    Aún no hay productos. Crea el primero con “Nuevo”.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($productos->hasPages())
            <div class="card-body border-top pb-0">
                {{ $productos->links() }}
            </div>
        @endif
    </div>
</div>
