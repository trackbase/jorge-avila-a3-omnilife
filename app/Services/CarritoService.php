<?php

namespace App\Services;

use App\Models\Producto;
use Illuminate\Support\Collection;

/**
 * Carrito en la sesión (Redis): guarda solo [producto_id => cantidad].
 * Precios y stock siempre se leen de la base de datos, nunca del navegador.
 */
class CarritoService
{
    private const LLAVE = 'carrito';

    /** Suma unidades sin pasar del stock; devuelve la cantidad final del producto en el carrito. */
    public function agregar(int $productoId, int $cantidad = 1): int
    {
        $producto = Producto::visibles()->findOrFail($productoId);
        $actual = $this->cantidades()[$productoId] ?? 0;

        return $this->guardar($producto, $actual + max(1, $cantidad));
    }

    /** Fija la cantidad (entre 1 y el stock); con 0 o menos quita el producto. */
    public function actualizar(int $productoId, int $cantidad): int
    {
        if ($cantidad <= 0) {
            $this->quitar($productoId);

            return 0;
        }

        return $this->guardar(Producto::visibles()->findOrFail($productoId), $cantidad);
    }

    public function quitar(int $productoId): void
    {
        $cantidades = $this->cantidades();
        unset($cantidades[$productoId]);

        session()->put(self::LLAVE, $cantidades);
    }

    public function vaciar(): void
    {
        session()->forget(self::LLAVE);
    }

    public function contar(): int
    {
        return array_sum($this->cantidades());
    }

    /**
     * Líneas con su producto y subtotales. Se sincroniza con la base: descarta lo que ya no es
     * visible o se agotó y recorta cantidades que superan el stock actual.
     *
     * @return Collection<int, array{producto: Producto, cantidad: int, subtotal_mxn: float, subtotal_usd: float}>
     */
    public function lineas(): Collection
    {
        $cantidades = $this->cantidades();
        $productos = Producto::visibles()->where('stock', '>', 0)->findMany(array_keys($cantidades))->keyBy('id');

        $lineas = collect($cantidades)
            ->filter(fn ($cantidad, $id) => $productos->has($id))
            ->map(function ($cantidad, $id) use ($productos) {
                $producto = $productos[$id];
                $cantidad = min($cantidad, $producto->stock);

                return [
                    'producto' => $producto,
                    'cantidad' => $cantidad,
                    'subtotal_mxn' => round($producto->precio_mxn * $cantidad, 2),
                    'subtotal_usd' => round($producto->precio_usd * $cantidad, 2),
                ];
            });

        session()->put(self::LLAVE, $lineas->map(fn ($linea) => $linea['cantidad'])->all());

        return $lineas->values();
    }

    /** @return array{subtotal_mxn: float, iva_mxn: float, total_mxn: float, subtotal_usd: float, iva_usd: float, total_usd: float} */
    public function totales(?Collection $lineas = null): array
    {
        $lineas ??= $this->lineas();
        $tasa = config('tienda.iva_tasa');

        $subtotalMxn = round($lineas->sum('subtotal_mxn'), 2);
        $subtotalUsd = round($lineas->sum('subtotal_usd'), 2);
        $ivaMxn = round($subtotalMxn * $tasa, 2);
        $ivaUsd = round($subtotalUsd * $tasa, 2);

        return [
            'subtotal_mxn' => $subtotalMxn,
            'iva_mxn' => $ivaMxn,
            'total_mxn' => round($subtotalMxn + $ivaMxn, 2),
            'subtotal_usd' => $subtotalUsd,
            'iva_usd' => $ivaUsd,
            'total_usd' => round($subtotalUsd + $ivaUsd, 2),
        ];
    }

    /** @return array<int, int> */
    private function cantidades(): array
    {
        return session()->get(self::LLAVE, []);
    }

    private function guardar(Producto $producto, int $cantidad): int
    {
        $cantidad = min($cantidad, $producto->stock);
        $cantidades = $this->cantidades();

        if ($cantidad > 0) {
            $cantidades[$producto->id] = $cantidad;
        } else {
            unset($cantidades[$producto->id]);
        }

        session()->put(self::LLAVE, $cantidades);

        return $cantidad;
    }
}
