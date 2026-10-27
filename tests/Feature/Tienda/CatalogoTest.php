<?php

namespace Tests\Feature\Tienda;

use App\Livewire\Tienda\Catalogo;
use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_muestra_solo_productos_visibles(): void
    {
        Producto::factory()->create(['nombre' => 'Producto Visible']);
        Producto::factory()->inactivo()->create(['nombre' => 'Producto Inactivo']);
        Producto::factory()->vencido()->create(['nombre' => 'Producto Vencido']);
        Producto::factory()->create(['nombre' => 'Producto Eliminado'])->delete();

        $this->get('/')
            ->assertOk()
            ->assertSee('Producto Visible')
            ->assertDontSee('Producto Inactivo')
            ->assertDontSee('Producto Vencido')
            ->assertDontSee('Producto Eliminado');
    }

    public function test_filtra_por_categoria(): void
    {
        $bebidas = Categoria::factory()->create(['nombre' => 'Bebidas', 'slug' => 'bebidas']);
        Producto::factory()->for($bebidas)->create(['nombre' => 'Agua Fresa']);
        Producto::factory()->create(['nombre' => 'Crema Facial']);

        Livewire::test(Catalogo::class)
            ->set('categoria', 'bebidas')
            ->assertSee('Agua Fresa')
            ->assertDontSee('Crema Facial');
    }

    public function test_filtra_por_rango_de_precio_en_mxn(): void
    {
        Producto::factory()->create(['nombre' => 'Barato', 'precio_mxn' => 50]);
        Producto::factory()->create(['nombre' => 'Medio', 'precio_mxn' => 300]);
        Producto::factory()->create(['nombre' => 'Caro', 'precio_mxn' => 900]);

        Livewire::test(Catalogo::class)
            ->set('precioMin', '100')
            ->set('precioMax', '500')
            ->assertSee('Medio')
            ->assertDontSee('Barato')
            ->assertDontSee('Caro')
            ->set('precioMin', 'abc')
            ->assertSee('Barato');
    }

    public function test_filtra_por_disponibilidad(): void
    {
        Producto::factory()->create(['nombre' => 'Con Stock']);
        Producto::factory()->sinStock()->create(['nombre' => 'Sin Stock']);

        Livewire::test(Catalogo::class)
            ->set('disponibilidad', 'disponibles')
            ->assertSee('Con Stock')->assertDontSee('Sin Stock')
            ->set('disponibilidad', 'agotados')
            ->assertSee('Sin Stock')->assertDontSee('Con Stock');
    }

    public function test_limpiar_filtros_restablece_todo(): void
    {
        Producto::factory()->create(['nombre' => 'Uno', 'precio_mxn' => 50]);

        Livewire::test(Catalogo::class)
            ->set('precioMin', '100')
            ->assertSee('No encontramos productos')
            ->call('limpiarFiltros')
            ->assertSet('precioMin', '')
            ->assertSee('Uno');
    }

    public function test_agregar_desde_el_catalogo_avisa_al_icono_del_carrito(): void
    {
        $producto = Producto::factory()->create(['stock' => 1]);

        Livewire::test(Catalogo::class)
            ->call('agregar', $producto->id)
            ->assertDispatched('carrito-actualizado')
            ->assertNotDispatched('toast', tipo: 'warning')
            ->call('agregar', $producto->id)
            ->assertDispatched('toast', tipo: 'warning');

        $this->assertSame([$producto->id => 1], session('carrito'));
    }
}
