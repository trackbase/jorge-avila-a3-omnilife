<?php

namespace App\Services\Pagos;

interface PasarelaPago
{
    public function cobrar(string $numeroTarjeta, string $monto, string $referencia): ResultadoPago;
}
