<div class="row justify-content-center py-lg-5">
    <div class="col-sm-10 col-md-6 col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h1 class="h4 mb-1">Acceso administrativo</h1>
                <p class="text-body-secondary small mb-4">Inicia sesión para administrar el catálogo y consultar reportes.</p>

                <form wire:submit="login" novalidate>
                    <div class="mb-3">
                        <label for="email" class="form-label">Correo electrónico</label>
                        <input id="email" type="email" wire:model.blur="email" autocomplete="username" autofocus
                               class="form-control @error('email') is-invalid @enderror">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input id="password" type="password" wire:model="password" autocomplete="current-password"
                               class="form-control @error('password') is-invalid @enderror">
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-check mb-4">
                        <input id="recordar" type="checkbox" wire:model="recordar" class="form-check-input">
                        <label for="recordar" class="form-check-label">Recordarme</label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100" wire:loading.attr="disabled" wire:target="login">
                        <span wire:loading.remove wire:target="login"><i class="bi bi-box-arrow-in-right"></i> Entrar</span>
                        <span wire:loading wire:target="login"><span class="spinner-border spinner-border-sm"></span> Validando…</span>
                    </button>
                </form>

                <x-aviso-demo class="text-body-secondary mt-4" />
            </div>
        </div>
    </div>
</div>
