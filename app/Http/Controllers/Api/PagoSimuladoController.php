<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Banco simulado: decide el resultado solo por el número de tarjeta (D5). */
class PagoSimuladoController extends Controller
{
    private const TARJETA_APROBADA = '4242424242424242';

    private const TARJETA_RECHAZADA = '4000000000000002';

    public function __invoke(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'numero_tarjeta' => ['required', 'digits_between:13,19'],
            'monto' => ['required', 'numeric', 'gt:0'],
            'referencia' => ['required', 'string', 'max:40'],
        ]);

        $ultimos4 = substr($datos['numero_tarjeta'], -4);

        return match ($datos['numero_tarjeta']) {
            self::TARJETA_APROBADA => response()->json([
                'estatus' => 'aprobado',
                'referencia' => 'SIM-'.Str::upper(Str::random(12)),
                'ultimos4' => $ultimos4,
                'mensaje' => 'Pago aprobado.',
            ]),
            self::TARJETA_RECHAZADA => response()->json([
                'estatus' => 'rechazado',
                'ultimos4' => $ultimos4,
                'mensaje' => 'El banco rechazó el pago: fondos insuficientes.',
            ], 402),
            default => response()->json([
                'estatus' => 'invalida',
                'ultimos4' => $ultimos4,
                'mensaje' => 'Tarjeta inválida.',
            ], 422),
        };
    }
}
