<?php

namespace App\Providers;

use App\Services\Pagos\PagoSimulado;
use App\Services\Pagos\PasarelaPago;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Cambiar a una pasarela real (Stripe, Openpay…) es implementar la interfaz y cambiar esta línea.
        $this->app->bind(PasarelaPago::class, PagoSimulado::class);
    }

    public function boot(): void
    {
        //
    }
}
