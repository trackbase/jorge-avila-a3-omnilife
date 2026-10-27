<div>
    <a href="{{ route('tienda.carrito') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Carrito</a>
    <h1 class="h3 mb-3">Pagar pedido</h1>

    @error('pago')
        <div class="alert alert-danger d-flex gap-2" role="alert">
            <i class="bi bi-exclamation-octagon"></i>
            <div>{{ $message }}</div>
        </div>
    @enderror

    @if ($lineas->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <p class="text-body-secondary">Tu carrito quedó vacío.</p>
                <a href="{{ route('tienda.catalogo') }}" class="btn btn-primary">Ver catálogo</a>
            </div>
        </div>
    @else
        <form wire:submit="pagar" class="row g-4">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <h2 class="h5">1. Tu correo</h2>
                        <p class="small text-body-secondary">Lo pedimos antes de generar el pedido para asociarle el folio.</p>
                        <label for="correo" class="form-label">Correo electrónico</label>
                        <input id="correo" type="email" required maxlength="255" autocomplete="email" wire:model.blur="correo"
                               class="form-control @error('correo') is-invalid @enderror">
                        @error('correo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h5">2. Pago con tarjeta <span class="badge text-bg-light fw-normal">simulado</span></h2>
                        <div class="alert alert-light border small py-2">
                            <i class="bi bi-credit-card"></i> Tarjetas de prueba:
                            <span class="font-monospace">4242 4242 4242 4242</span> aprobada ·
                            <span class="font-monospace">4000 0000 0000 0002</span> rechazada · cualquier otra, inválida.
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <label for="titular" class="form-label">Nombre del titular</label>
                                <input id="titular" type="text" required maxlength="80" autocomplete="cc-name" wire:model.blur="titular"
                                       class="form-control @error('titular') is-invalid @enderror">
                                @error('titular') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label for="numero" class="form-label">Número de tarjeta</label>
                                <input id="numero" type="text" required inputmode="numeric" maxlength="19" autocomplete="cc-number"
                                       x-on:input.capture="$el.value = $el.value.replace(/\D/g, '')" wire:model.blur="numeroTarjeta"
                                       class="form-control font-monospace @error('numeroTarjeta') is-invalid @enderror">
                                @error('numeroTarjeta') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-6">
                                <label for="vencimiento" class="form-label">Vencimiento</label>
                                <input id="vencimiento" type="text" required inputmode="numeric" maxlength="5" placeholder="MM/AA" autocomplete="cc-exp"
                                       x-on:input.capture="$el.value = $el.value.replace(/[^\d\/]/g, '').replace(/^(\d{2})(\d)/, '$1/$2')" wire:model.blur="vencimiento"
                                       class="form-control font-monospace @error('vencimiento') is-invalid @enderror">
                                @error('vencimiento') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-6">
                                <label for="cvv" class="form-label">CVV</label>
                                <input id="cvv" type="password" required inputmode="numeric" maxlength="4" autocomplete="cc-csc"
                                       x-on:input.capture="$el.value = $el.value.replace(/\D/g, '')" wire:model.blur="cvv"
                                       class="form-control font-monospace @error('cvv') is-invalid @enderror">
                                @error('cvv') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <p class="small text-body-secondary mt-3 mb-0">
                            <i class="bi bi-shield-lock"></i> Solo guardamos los últimos 4 dígitos y la referencia del banco.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h5 mb-3">Tu pedido</h2>
                        <ul class="list-unstyled small mb-3">
                            @foreach ($lineas as $linea)
                                <li class="d-flex justify-content-between gap-2 py-1 border-bottom" wire:key="resumen-{{ $linea['producto']->id }}">
                                    <span>{{ $linea['cantidad'] }} × {{ $linea['producto']->nombre }}</span>
                                    <span class="text-nowrap">${{ number_format($linea['subtotal_mxn'], 2) }}</span>
                                </li>
                            @endforeach
                        </ul>
                        <x-resumen-totales :$totales />
                        <button type="submit" class="btn btn-primary btn-lg w-100 mt-3" wire:loading.attr="disabled" wire:target="pagar">
                            <span wire:loading.remove wire:target="pagar"><i class="bi bi-lock"></i> Pagar ${{ number_format($totales['total_mxn'], 2) }} MXN</span>
                            <span wire:loading wire:target="pagar"><span class="spinner-border spinner-border-sm"></span> Procesando pago…</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    @endif
</div>
