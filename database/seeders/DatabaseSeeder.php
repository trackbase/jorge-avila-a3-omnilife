<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Administrador',
            'email' => 'admin@tienda.test',
            'password' => 'Admin123!',
        ]);

        $this->call([
            CategoriaSeeder::class,
            ProductoSeeder::class,
            PedidoSeeder::class,
        ]);
    }
}
