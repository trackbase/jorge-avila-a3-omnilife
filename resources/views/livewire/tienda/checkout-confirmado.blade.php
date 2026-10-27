<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-lg-5 text-center">
                <i class="bi bi-check-circle-fill text-success display-4"></i>
                <h1 class="h3 mt-3">¡Gracias por tu compra!</h1>
                <p class="text-body-secondary">Tu pago fue aprobado. Guarda tu folio para cualquier aclaración.</p>
                <p class="display-6 font-monospace text-primary mb-4">{{ $pedido->folio }}</p>

                <ul class="list-group list-group-flush text-start mb-4">
                    @foreach ($pedido->detalles as $detalle)
                        <li class="list-group-item d-flex justify-content-between px-0" wire:key="detalle-{{ $detalle->id }}">
                            <span>{{ $detalle->cantidad }} × {{ $detalle->nombre }}</span>
                            <span>${{ number_format($detalle->precio_mxn * $detalle->cantidad, 2) }}</span>
                        </li>
                    @endforeach
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-body-secondary">IVA</span>
                        <span>${{ number_format($pedido->impuestos, 2) }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 fw-bold">
                        <span>Total</span>
                        <span>${{ number_format($pedido->total, 2) }} MXN · ${{ number_format($pedido->total_usd, 2) }} USD</span>
                    </li>
                </ul>

                <dl class="row small text-start text-body-secondary mb-4">
                    <dt class="col-5">Correo</dt>
                    <dd class="col-7">{{ $pedido->correo }}</dd>
                    <dt class="col-5">Tarjeta</dt>
                    <dd class="col-7 font-monospace">•••• {{ $pedido->tarjeta_ultimos4 }}</dd>
                    <dt class="col-5">Referencia del banco</dt>
                    <dd class="col-7 font-monospace">{{ $pedido->referencia_pago }}</dd>
                </dl>

                <a href="{{ route('tienda.catalogo') }}" class="btn btn-primary">Seguir comprando</a>
            </div>
        </div>
    </div>
</div>
