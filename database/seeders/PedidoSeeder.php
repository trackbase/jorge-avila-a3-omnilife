<?php

namespace Database\Seeders;

use App\Enums\EstatusPedido;
use App\Models\Pedido;
use App\Models\Producto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PedidoSeeder extends Seeder
{
    /** Pedidos por mes, del más antiguo al actual: crecen para que la gráfica muestre tendencia. */
    private const PEDIDOS_POR_MES = [14, 17, 20, 23, 26, 30];

    /** Los más vendidos: aparecen 6 veces en la bolsa de sorteo para que el Top 3 sea claro. */
    private const POPULARES = ['OMN-NUT-001', 'OMN-BEB-001', 'OMN-VIT-002'];

    public function run(): void
    {
        fake()->seed(2026);
        $iva = config('tienda.iva_tasa');

        $bolsa = Producto::withTrashed()->get()
            ->flatMap(fn (Producto $producto) => array_fill(0, in_array($producto->sku, self::POPULARES) ? 6 : 1, $producto));

        $fechas = collect();
        foreach (self::PEDIDOS_POR_MES as $indice => $cantidad) {
            $inicio = now()->subMonthsNoOverflow(count(self::PEDIDOS_POR_MES) - 1 - $indice)->startOfMonth();
            $fin = $indice === count(self::PEDIDOS_POR_MES) - 1 ? now() : $inicio->copy()->endOfMonth();

            for ($i = 0; $i < $cantidad; $i++) {
                $fechas->push(Carbon::instance(fake()->dateTimeBetween($inicio, $fin)));
            }
        }

        foreach ($fechas->sort() as $fecha) {
            $lineas = collect(range(1, fake()->numberBetween(1, 3)))
                ->map(fn () => fake()->randomElement($bolsa->all()))
                ->unique('id')
                ->map(fn (Producto $producto) => [
                    'producto_id' => $producto->id,
                    'sku' => $producto->sku,
                    'nombre' => $producto->nombre,
                    'cantidad' => fake()->numberBetween(1, 3),
                    'precio_usd' => $producto->precio_usd,
                    'precio_mxn' => $producto->precio_mxn,
                ]);

            $subtotal = round($lineas->sum(fn ($linea) => $linea['cantidad'] * $linea['precio_mxn']), 2);
            $subtotalUsd = $lineas->sum(fn ($linea) => $linea['cantidad'] * $linea['precio_usd']);
            $impuestos = round($subtotal * $iva, 2);

            $azar = fake()->numberBetween(1, 100);
            $estatus = match (true) {
                $azar <= 85 => EstatusPedido::Pagado,
                $azar <= 95 => EstatusPedido::Rechazado,
                default => EstatusPedido::Pendiente,
            };

            $pedido = Pedido::create([
                'correo' => fake()->safeEmail(),
                'subtotal' => $subtotal,
                'impuestos' => $impuestos,
                'total' => round($subtotal + $impuestos, 2),
                'total_usd' => round($subtotalUsd * (1 + $iva), 2),
                'tipo_cambio' => ProductoSeeder::TIPO_CAMBIO,
                'estatus' => $estatus,
                'fecha' => $fecha,
                'referencia_pago' => $estatus === EstatusPedido::Pagado ? 'SIM-'.Str::upper(Str::random(12)) : null,
                'tarjeta_ultimos4' => match ($estatus) {
                    EstatusPedido::Pagado => '4242',
                    EstatusPedido::Rechazado => '0002',
                    default => null,
                },
            ]);

            $pedido->update(['folio' => sprintf('PED-%d-%06d', $fecha->year, $pedido->id)]);
            $pedido->detalles()->createMany($lineas->values()->all());
        }
    }
}
