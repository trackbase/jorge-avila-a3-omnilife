<?php

namespace Tests\Feature\Tienda;

use App\Livewire\Tienda\ProductoDetalle;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductoDetalleTest extends TestCase
{
    use RefreshDatabase;

    public function test_muestra_el_detalle_de_un_producto_visible(): void
    {
        $producto = Producto::factory()->create([
            'sku' => 'DET-001',
            'nombre' => 'Batido Detalle',
            'descripcion_larga' => 'Texto largo del detalle.',
            'precio_usd' => 10,
            'precio_mxn' => 185.5,
        ]);

        $this->get(route('tienda.producto', $producto->sku))
            ->assertOk()
            ->assertSee(['DET-001', 'Batido Detalle', 'Texto largo del detalle.', '$185.50', '$10.00', $producto->imagen_url])
            ->assertSee('Agregar al carrito');
    }

    public function test_responde_404_si_el_producto_no_es_visible(): void
    {
        $inactivo = Producto::factory()->inactivo()->create();
        $vencido = Producto::factory()->vencido()->create();
        $eliminado = Producto::factory()->create();
        $eliminado->delete();

        foreach ([$inactivo, $vencido, $eliminado] as $producto) {
            $this->get(route('tienda.producto', $producto->sku))->assertNotFound();
        }

        $this->get(route('tienda.producto', 'NO-EXISTE'))->assertNotFound();
    }

    public function test_agregar_con_cantidad_respeta_el_stock(): void
    {
        $producto = Producto::factory()->create(['stock' => 3]);

        Livewire::test(ProductoDetalle::class, ['sku' => $producto->sku])
            ->call('agregar', cantidad: 2)
            ->assertDispatched('carrito-actualizado')
            ->call('agregar', cantidad: 5)
            ->assertDispatched('toast', tipo: 'warning');

        $this->assertSame([$producto->id => 3], session('carrito'));
    }
}
