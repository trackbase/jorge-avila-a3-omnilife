<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Pedido;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_genera_admin_catalogo_e_historial_de_seis_meses(): void
    {
        Storage::fake('public');

        $this->seed();

        $this->assertDatabaseHas('users', ['email' => 'admin@tienda.test']);
        $this->assertSame(5, Categoria::count());
        $this->assertSame(30, Producto::withTrashed()->count());
        Storage::disk('public')->assertExists(Producto::withTrashed()->pluck('imagen')->all());

        $meses = Pedido::pluck('fecha')->map->format('Y-m')->unique();
        $this->assertCount(6, $meses);
        $this->assertTrue(Pedido::whereNull('folio')->doesntExist());
        $this->assertTrue(Pedido::doesntHave('detalles')->doesntExist());
    }
}
