<?php

namespace Database\Factories;

use App\Enums\EstatusPedido;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pedido>
 */
class PedidoFactory extends Factory
{
    public function definition(): array
    {
        $tipoCambio = 18.30;
        $subtotal = fake()->randomFloat(2, 200, 3000);
        $impuestos = round($subtotal * config('tienda.iva_tasa'), 2);
        $total = round($subtotal + $impuestos, 2);

        return [
            'folio' => fake()->unique()->numerify('PED-'.now()->year.'-######'),
            'correo' => fake()->safeEmail(),
            'subtotal' => $subtotal,
            'impuestos' => $impuestos,
            'total' => $total,
            'total_usd' => round($total / $tipoCambio, 2),
            'tipo_cambio' => $tipoCambio,
            'estatus' => EstatusPedido::Pagado,
            'fecha' => fake()->dateTimeBetween('-6 months'),
            'referencia_pago' => 'SIM-'.Str::upper(Str::random(12)),
            'tarjeta_ultimos4' => '4242',
        ];
    }

    public function rechazado(): static
    {
        return $this->state([
            'estatus' => EstatusPedido::Rechazado,
            'referencia_pago' => null,
            'tarjeta_ultimos4' => '0002',
        ]);
    }

    public function pendiente(): static
    {
        return $this->state([
            'estatus' => EstatusPedido::Pendiente,
            'referencia_pago' => null,
            'tarjeta_ultimos4' => null,
        ]);
    }
}
