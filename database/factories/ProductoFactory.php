<?php

namespace Database\Factories;

use App\Models\Categoria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Producto>
 */
class ProductoFactory extends Factory
{
    public function definition(): array
    {
        $precioUsd = fake()->randomFloat(2, 5, 120);

        return [
            'categoria_id' => Categoria::factory(),
            'sku' => fake()->unique()->bothify('TST-###-??##'),
            'nombre' => 'Producto '.fake()->unique()->numerify('####'),
            'descripcion_corta' => fake()->sentence(),
            'descripcion_larga' => fake()->paragraph(),
            'precio_usd' => $precioUsd,
            'precio_mxn' => round($precioUsd * 18.30, 2),
            'imagen' => 'productos/demo.svg',
            'stock' => fake()->numberBetween(5, 50),
            'fecha_vigencia' => now()->addMonths(6)->toDateString(),
            'activo' => true,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(['activo' => false]);
    }

    public function vencido(): static
    {
        return $this->state(['fecha_vigencia' => now()->subDay()->toDateString()]);
    }

    public function sinStock(): static
    {
        return $this->state(['stock' => 0]);
    }
}
