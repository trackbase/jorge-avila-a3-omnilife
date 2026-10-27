<?php

namespace App\Services\Pagos;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/** Consume el mock REST POST /api/pagos/simular como si fuera un banco externo. */
class PagoSimulado implements PasarelaPago
{
    public function cobrar(#[\SensitiveParameter] string $numeroTarjeta, string $monto, string $referencia): ResultadoPago
    {
        try {
            // Sin retry: reintentar un cobro puede duplicar el cargo.
            $respuesta = Http::acceptJson()->timeout(10)->post(route('api.pagos.simular'), [
                'numero_tarjeta' => $numeroTarjeta,
                'monto' => $monto,
                'referencia' => $referencia,
            ]);
        } catch (ConnectionException) {
            return new ResultadoPago(false, null, 'No pudimos contactar al banco. Intenta de nuevo.');
        }

        return new ResultadoPago(
            aprobado: $respuesta->json('estatus') === 'aprobado',
            referencia: $respuesta->json('referencia'),
            mensaje: $respuesta->json('mensaje') ?? 'Respuesta inesperada del banco.',
        );
    }
}
