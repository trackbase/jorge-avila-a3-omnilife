<?php

namespace App\Services;

use App\Enums\EstatusPedido;
use App\Exceptions\PedidoNoConfirmado;
use App\Exceptions\TipoCambioNoDisponible;
use App\Models\Pedido;
use App\Models\Producto;
use App\Services\Pagos\PasarelaPago;
use Illuminate\Support\Facades\DB;

class PedidoService
{
    public function __construct(
        private CarritoService $carrito,
        private TipoCambioService $tipoCambio,
        private PasarelaPago $pasarela,
    ) {}

    /**
     * Flujo A8: pre-pedido "pendiente" → cobro → "pagado" (descuenta stock) o "rechazado".
     * El cobro va fuera de las transacciones para no bloquear filas mientras responde el banco.
     *
     * @throws PedidoNoConfirmado
     */
    public function confirmar(string $correo, #[\SensitiveParameter] string $numeroTarjeta): Pedido
    {
        $pedido = $this->crearPrePedido($correo);

        $pago = $this->pasarela->cobrar($numeroTarjeta, $pedido->total, $pedido->folio);
        $ultimos4 = substr($numeroTarjeta, -4);

        if (! $pago->aprobado) {
            $pedido->update(['estatus' => EstatusPedido::Rechazado, 'tarjeta_ultimos4' => $ultimos4]);

            throw new PedidoNoConfirmado($pago->mensaje);
        }

        if (! $this->descontarStock($pedido, $pago->referencia, $ultimos4)) {
            throw new PedidoNoConfirmado("El pago se aprobó, pero otro cliente se llevó el último stock mientras lo procesábamos. Cancelamos el pedido {$pedido->folio} y el cargo se reembolsará.");
        }

        $this->carrito->vaciar();

        return $pedido;
    }

    private function crearPrePedido(string $correo): Pedido
    {
        $cantidades = $this->carrito->cantidades();

        if ($cantidades === []) {
            throw new PedidoNoConfirmado('Tu carrito está vacío.');
        }

        $tipoCambio = $this->tipoCambioActual();

        return DB::transaction(function () use ($correo, $cantidades, $tipoCambio) {
            // lockForUpdate: nadie más modifica el stock de estos productos hasta el commit.
            $productos = Producto::visibles()->whereKey(array_keys($cantidades))->lockForUpdate()->get()->keyBy('id');

            $lineas = collect($cantidades)->map(function ($cantidad, $id) use ($productos) {
                $producto = $productos->get($id);

                if (! $producto) {
                    throw new PedidoNoConfirmado('Un producto de tu carrito ya no está disponible. Revisa tu carrito.');
                }

                if ($producto->stock < $cantidad) {
                    throw new PedidoNoConfirmado("Ya no hay suficiente stock de {$producto->nombre} (quedan {$producto->stock}). Ajustamos tu carrito.");
                }

                return $this->carrito->linea($producto, $cantidad);
            });

            $totales = $this->carrito->totales($lineas);

            $pedido = Pedido::create([
                'correo' => $correo,
                'subtotal' => $totales['subtotal_mxn'],
                'impuestos' => $totales['iva_mxn'],
                'total' => $totales['total_mxn'],
                'total_usd' => $totales['total_usd'],
                'tipo_cambio' => $tipoCambio ?? round($totales['subtotal_mxn'] / $totales['subtotal_usd'], 4),
                'estatus' => EstatusPedido::Pendiente,
                'fecha' => now(),
            ]);

            $pedido->update(['folio' => sprintf('PED-%d-%06d', $pedido->fecha->year, $pedido->id)]);

            $pedido->detalles()->createMany($lineas->map(fn ($linea) => [
                'producto_id' => $linea['producto']->id,
                'sku' => $linea['producto']->sku,
                'nombre' => $linea['producto']->nombre,
                'cantidad' => $linea['cantidad'],
                'precio_usd' => $linea['producto']->precio_usd,
                'precio_mxn' => $linea['producto']->precio_mxn,
            ])->values()->all());

            return $pedido;
        });
    }

    /** R27: revalida el stock con lockForUpdate al confirmar; si ya no alcanza, el pedido queda cancelado. */
    private function descontarStock(Pedido $pedido, ?string $referencia, string $ultimos4): bool
    {
        return DB::transaction(function () use ($pedido, $referencia, $ultimos4) {
            $detalles = $pedido->detalles;
            $productos = Producto::whereKey($detalles->pluck('producto_id'))->lockForUpdate()->get()->keyBy('id');

            $alcanza = $detalles->every(fn ($detalle) => ($productos->get($detalle->producto_id)?->stock ?? 0) >= $detalle->cantidad);

            if ($alcanza) {
                $detalles->each(fn ($detalle) => $productos[$detalle->producto_id]->decrement('stock', $detalle->cantidad));
            }

            $pedido->update([
                'estatus' => $alcanza ? EstatusPedido::Pagado : EstatusPedido::Cancelado,
                'referencia_pago' => $referencia,
                'tarjeta_ultimos4' => $ultimos4,
            ]);

            return $alcanza;
        });
    }

    /** El tipo de cambio es informativo en el pedido: si no hay API ni valor guardado, se usa el implícito en los precios. */
    private function tipoCambioActual(): ?float
    {
        try {
            return $this->tipoCambio->obtener()['valor'];
        } catch (TipoCambioNoDisponible) {
            return null;
        }
    }
}
