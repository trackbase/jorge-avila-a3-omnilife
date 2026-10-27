<div>
    <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-0">Panel de administración</h1>
            <p class="text-body-secondary mb-0">Hola, {{ auth()->user()->name }}. Ventas netas sin IVA de pedidos pagados.</p>
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
            <div class="btn-group btn-group-sm" role="group" aria-label="Agrupar por">
                <input id="por-mes" type="radio" class="btn-check" value="mes" wire:model.live="agrupacion">
                <label for="por-mes" class="btn btn-outline-primary">Mes</label>
                <input id="por-semana" type="radio" class="btn-check" value="semana" wire:model.live="agrupacion">
                <label for="por-semana" class="btn btn-outline-primary">Semana</label>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4" wire:loading.class="opacity-50">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="small text-body-secondary mb-1"><i class="bi bi-cash-stack"></i> Ganancia del periodo</p>
                    <p class="fs-3 fw-bold text-primary mb-0">${{ number_format($resumen['ganancia'], 2) }}</p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="small text-body-secondary mb-1"><i class="bi bi-bag-check"></i> Pedidos pagados</p>
                    <p class="fs-3 fw-bold mb-0">{{ $resumen['pedidos'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="small text-body-secondary mb-1"><i class="bi bi-receipt"></i> Ticket promedio (con IVA)</p>
                    <p class="fs-3 fw-bold mb-0">${{ number_format($resumen['ticket_promedio'], 2) }}</p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="small text-body-secondary mb-1"><i class="bi bi-currency-exchange"></i> Tipo de cambio USD/MXN</p>
                    @if ($tipoCambio)
                        <p class="fs-3 fw-bold mb-0">{{ number_format($tipoCambio['valor'], 4) }}</p>
                        <p class="small text-body-secondary mb-0">{{ $tipoCambio['fuente'] }} · {{ \Illuminate\Support\Carbon::parse($tipoCambio['fecha'])->format('d/m/Y') }}</p>
                    @else
                        <p class="text-body-secondary mb-0">No disponible</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5">Ganancias por {{ $agrupacion === 'semana' ? 'semana' : 'mes' }}</h2>
                    {{-- wire:ignore: Livewire no toca el canvas; Alpine actualiza la gráfica con el evento grafica-actualizada. --}}
                    <div wire:ignore x-data="graficaGanancias(@js($grafica))"
                         x-on:grafica-actualizada.window="actualizar($event.detail.datos)" style="height: 320px">
                        <canvas x-ref="lienzo" aria-label="Gráfica de ganancias por periodo" role="img"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5">Top 3 más vendidos</h2>
                    <p class="small text-body-secondary">Unidades en pedidos pagados del periodo (incluye productos eliminados).</p>
                    <ol class="list-group list-group-numbered list-group-flush" wire:loading.class="opacity-50">
                        @forelse ($top as $producto)
                            <li class="list-group-item d-flex justify-content-between align-items-start px-0" wire:key="top-{{ $producto->sku }}">
                                <div class="ms-2 me-auto">
                                    <div class="fw-semibold">{{ $producto->nombre }}</div>
                                    <span class="font-monospace small text-body-secondary">{{ $producto->sku }}</span>
                                    @if ($producto->eliminado)
                                        <span class="badge text-bg-secondary">Eliminado</span>
                                    @endif
                                    <div class="small text-body-secondary">${{ number_format($producto->importe, 2) }} MXN</div>
                                </div>
                                <span class="badge bg-primary rounded-pill">{{ $producto->unidades }} u.</span>
                            </li>
                        @empty
                            <li class="list-group-item px-0 text-body-secondary">Sin ventas en el periodo.</li>
                        @endforelse
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
