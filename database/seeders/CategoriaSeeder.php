<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Nutrición', 'Bebidas', 'Vitaminas y minerales', 'Cuidado personal', 'Bienestar'] as $nombre) {
            Categoria::create(['nombre' => $nombre, 'slug' => Str::slug($nombre)]);
        }
    }
}
