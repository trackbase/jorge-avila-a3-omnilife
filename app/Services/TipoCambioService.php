<?php

namespace App\Services;

use App\Exceptions\TipoCambioNoDisponible;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TipoCambioService
{
    public const LLAVE = 'tipo_cambio:usd_mxn';

    public const LLAVE_ULTIMO = 'tipo_cambio:ultimo';

    private const URL_BANXICO = 'https://www.banxico.org.mx/SieAPIRest/service/v1/series/SF43718/datos/oportuno';

    private const URL_FRANKFURTER = 'https://api.frankfurter.dev/v1/latest?base=USD&symbols=MXN';

    /**
     * Pesos por dólar, cacheados en Redis por TIPO_CAMBIO_TTL segundos.
     * Orden: Banxico (FIX) → Frankfurter → último valor conocido (llave sin expiración).
     *
     * @return array{valor: float, fecha: string, fuente: string}
     */
    public function obtener(): array
    {
        return Cache::remember(self::LLAVE, config('tienda.tipo_cambio_ttl'), function () {
            $tipoCambio = $this->consultarBanxico() ?? $this->consultarFrankfurter();

            if ($tipoCambio) {
                Cache::forever(self::LLAVE_ULTIMO, $tipoCambio);

                return $tipoCambio;
            }

            return Cache::get(self::LLAVE_ULTIMO) ?? throw new TipoCambioNoDisponible;
        });
    }

    private function consultarBanxico(): ?array
    {
        $token = config('services.banxico.token');

        if (blank($token)) {
            return null;
        }

        try {
            $dato = $this->http()->withHeaders(['Bmx-Token' => $token])->get(self::URL_BANXICO)->json('bmx.series.0.datos.0');
        } catch (ConnectionException|RequestException $e) {
            Log::warning('Banxico no respondió', ['error' => $e->getMessage()]);

            return null;
        }

        // Banxico responde "N/E" cuando el dato aún no está publicado.
        if (! is_numeric($dato['dato'] ?? null)) {
            return null;
        }

        return [
            'valor' => (float) $dato['dato'],
            'fecha' => Carbon::createFromFormat('d/m/Y', $dato['fecha'])->toDateString(),
            'fuente' => 'Banxico FIX',
        ];
    }

    private function consultarFrankfurter(): ?array
    {
        try {
            $respuesta = $this->http()->get(self::URL_FRANKFURTER)->json();
        } catch (ConnectionException|RequestException $e) {
            Log::warning('Frankfurter no respondió', ['error' => $e->getMessage()]);

            return null;
        }

        if (! is_numeric($respuesta['rates']['MXN'] ?? null)) {
            return null;
        }

        return [
            'valor' => (float) $respuesta['rates']['MXN'],
            'fecha' => $respuesta['date'],
            'fuente' => 'Frankfurter (BCE)',
        ];
    }

    private function http(): PendingRequest
    {
        return Http::acceptJson()->timeout(5)->retry(2, 200);
    }
}
