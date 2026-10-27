<div>
    <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-3">
        <div>
            <h1 class="h3 mb-0">Pedidos</h1>
            <p class="text-body-secondary small mb-0">
                {{ $pedidos->total() }} pedidos en el rango · ${{ number_format($totalPagado, 2) }} MXN cobrados (pagados, con IVA)
            </p>
        </div>
        <div class="d-flex flex-wrap align-items-end gap-2">
            <div>
                <label for="desde" class="form-label small mb-1">Desde</label>
                <input id="desde" type="date" class="form-control form-control-sm" wire:model.live="desde">
            </div>
            <div>
                <label for="hasta" class="form-label small mb-1">Hasta</label>
                <input id="hasta" type="date" class="form-control form-control-sm" wire:model.live="hasta">
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="limpiarFechas">Limpiar</button>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Folio</th>
                        <th scope="col">Fecha</th>
                        <th scope="col">Correo</th>
                        <th scope="col" class="text-end">Subtotal</th>
                        <th scope="col" class="text-end">IVA</th>
                        <th scope="col" class="text-end">Total MXN</th>
                        <th scope="col" class="text-end">Total USD</th>
                        <th scope="col" class="text-center">Estatus</th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody wire:loading.class="opacity-50">
                    @forelse ($pedidos as $pedido)
                        <tr wire:key="pedido-{{ $pedido->id }}">
                            <td class="font-monospace small">{{ $pedido->folio }}</td>
                            <td class="text-nowrap">{{ $pedido->fecha->format('d/m/Y H:i') }}</td>
                            <td>{{ $pedido->correo }}</td>
                            <td class="text-end">${{ number_format($pedido->subtotal, 2) }}</td>
                            <td class="text-end">${{ number_format($pedido->impuestos, 2) }}</td>
                            <td class="text-end fw-semibold">${{ number_format($pedido->total, 2) }}</td>
                            <td class="text-end">${{ number_format($pedido->total_usd, 2) }}</td>
                            <td class="text-center">
                                <span class="badge text-bg-{{ $pedido->estatus->color() }}">{{ $pedido->estatus->etiqueta() }}</span>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary" title="Ver detalle" wire:click="verDetalle({{ $pedido->id }})">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-body-secondary py-5">
                                <i class="bi bi-receipt fs-1 d-block mb-2"></i>
                                No hay pedidos en ese rango de fechas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($pedidos->hasPages())
            <div class="card-body border-top pb-0">
                {{ $pedidos->links() }}
            </div>
        @endif
    </div>

    {{-- Modal con Alpine (D3): Livewire carga el detalle y lanza "abrir-detalle"; Alpine solo muestra u oculta. --}}
    <div x-data="{ abierto: false }" x-on:abrir-detalle.window="abierto = true" x-on:keydown.escape.window="abierto = false">
        <div class="modal d-block" tabindex="-1" role="dialog" aria-modal="true" x-show="abierto" x-cloak x-transition.opacity
             @click.self="abierto = false">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    @if ($detalle)
                        <div class="modal-header">
                            <h2 class="modal-title h5">Pedido <span class="font-monospace">{{ $detalle->folio }}</span></h2>
                            <button type="button" class="btn-close" aria-label="Cerrar" @click="abierto = false"></button>
                        </div>
                        <div class="modal-body">
                            <dl class="row small">
                                <dt class="col-sm-3">Fecha</dt>
                                <dd class="col-sm-9">{{ $detalle->fecha->format('d/m/Y H:i') }}</dd>
                                <dt class="col-sm-3">Correo</dt>
                                <dd class="col-sm-9">{{ $detalle->correo }}</dd>
                                <dt class="col-sm-3">Estatus</dt>
                                <dd class="col-sm-9"><span class="badge text-bg-{{ $detalle->estatus->color() }}">{{ $detalle->estatus->etiqueta() }}</span></dd>
                                <dt class="col-sm-3">Pago</dt>
                                <dd class="col-sm-9">
                                    {{ $detalle->tarjeta_ultimos4 ? '•••• '.$detalle->tarjeta_ultimos4 : '—' }}
                                    <span class="font-monospace text-body-secondary">{{ $detalle->referencia_pago }}</span>
                                </dd>
                                <dt class="col-sm-3">Tipo de cambio</dt>
                                <dd class="col-sm-9">{{ number_format($detalle->tipo_cambio, 4) }} MXN/USD</dd>
                            </dl>
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">SKU</th>
                                        <th scope="col">Producto</th>
                                        <th scope="col" class="text-end">Cant.</th>
                                        <th scope="col" class="text-end">Precio MXN</th>
                                        <th scope="col" class="text-end">Importe MXN</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($detalle->detalles as $linea)
                                        <tr wire:key="linea-{{ $linea->id }}">
                                            <td class="font-monospace small">{{ $linea->sku }}</td>
                                            <td>{{ $linea->nombre }}</td>
                                            <td class="text-end">{{ $linea->cantidad }}</td>
                                            <td class="text-end">${{ number_format($linea->precio_mxn, 2) }}</td>
                                            <td class="text-end">${{ number_format($linea->precio_mxn * $linea->cantidad, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr><th colspan="4" class="text-end fw-normal">Subtotal</th><td class="text-end">${{ number_format($detalle->subtotal, 2) }}</td></tr>
                                    <tr><th colspan="4" class="text-end fw-normal">IVA</th><td class="text-end">${{ number_format($detalle->impuestos, 2) }}</td></tr>
                                    <tr><th colspan="4" class="text-end">Total</th><td class="text-end fw-bold">${{ number_format($detalle->total, 2) }} · ${{ number_format($detalle->total_usd, 2) }} USD</td></tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="modal-backdrop show" x-show="abierto" x-cloak></div>
    </div>
</div>
