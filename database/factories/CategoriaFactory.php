<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Categoria>
 */
class CategoriaFactory extends Factory
{
    public function definition(): array
    {
        $nombre = 'Categoria '.fake()->unique()->numerify('###');

        return [
            'nombre' => $nombre,
            'slug' => Str::slug($nombre),
        ];
    }
}
