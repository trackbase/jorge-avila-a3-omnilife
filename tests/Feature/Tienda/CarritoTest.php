<?php

namespace Tests\Feature\Tienda;

use App\Livewire\Tienda\Carrito;
use App\Livewire\Tienda\CarritoIcono;
use App\Models\Producto;
use App\Services\CarritoService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CarritoTest extends TestCase
{
    use RefreshDatabase;

    private CarritoService $carrito;

    protected function setUp(): void
    {
        parent::setUp();

        $this->carrito = app(CarritoService::class);
    }

    public function test_agrega_y_acumula_cantidades(): void
    {
        $producto = Producto::factory()->create(['stock' => 10]);

        $this->assertSame(1, $this->carrito->agregar($producto->id));
        $this->assertSame(3, $this->carrito->agregar($producto->id, 2));
        $this->assertSame(3, $this->carrito->contar());
    }

    public function test_nunca_supera_el_stock(): void
    {
        $producto = Producto::factory()->create(['stock' => 2]);

        $this->assertSame(2, $this->carrito->agregar($producto->id, 5));
        $this->assertSame(2, $this->carrito->actualizar($producto->id, 9));
        $this->assertSame(2, $this->carrito->contar());
    }

    public function test_no_agrega_productos_agotados_ni_no_visibles(): void
    {
        $agotado = Producto::factory()->sinStock()->create();
        $inactivo = Producto::factory()->inactivo()->create();

        $this->assertSame(0, $this->carrito->agregar($agotado->id));
        $this->assertSame(0, $this->carrito->contar());

        $this->expectException(ModelNotFoundException::class);
        $this->carrito->agregar($inactivo->id);
    }

    public function test_cambia_cantidades_y_elimina(): void
    {
        $a = Producto::factory()->create(['stock' => 10]);
        $b = Producto::factory()->create(['stock' => 10]);
        $this->carrito->agregar($a->id, 2);
        $this->carrito->agregar($b->id);

        $this->assertSame(5, $this->carrito->actualizar($a->id, 5));
        $this->assertSame(0, $this->carrito->actualizar($b->id, 0));
        $this->assertSame([$a->id => 5], session('carrito'));

        $this->carrito->quitar($a->id);
        $this->assertSame(0, $this->carrito->contar());
    }

    public function test_las_lineas_se_sincronizan_con_el_stock_y_la_visibilidad(): void
    {
        $recortado = Producto::factory()->create(['stock' => 10]);
        $desactivado = Producto::factory()->create(['stock' => 10]);
        $this->carrito->agregar($recortado->id, 8);
        $this->carrito->agregar($desactivado->id);

        $recortado->update(['stock' => 4]);
        $desactivado->update(['activo' => false]);

        $lineas = $this->carrito->lineas();

        $this->assertCount(1, $lineas);
        $this->assertSame(4, $lineas->first()['cantidad']);
        $this->assertSame([$recortado->id => 4], session('carrito'));
    }

    public function test_calcula_subtotal_iva_y_total_en_mxn_y_usd(): void
    {
        $a = Producto::factory()->create(['precio_mxn' => 100.00, 'precio_usd' => 5.50, 'stock' => 10]);
        $b = Producto::factory()->create(['precio_mxn' => 49.99, 'precio_usd' => 2.75, 'stock' => 10]);
        $this->carrito->agregar($a->id, 2);
        $this->carrito->agregar($b->id);

        $this->assertSame([
            'subtotal_mxn' => 249.99,
            'iva_mxn' => 40.0,
            'total_mxn' => 289.99,
            'subtotal_usd' => 13.75,
            'iva_usd' => 2.2,
            'total_usd' => 15.95,
        ], $this->carrito->totales());
    }

    public function test_la_pagina_del_carrito_cambia_cantidades_y_quita_productos(): void
    {
        $producto = Producto::factory()->create(['nombre' => 'Batido Carrito', 'stock' => 3]);
        $this->carrito->agregar($producto->id);

        $this->get(route('tienda.carrito'))->assertOk()->assertSee('Batido Carrito');

        Livewire::test(Carrito::class)
            ->call('actualizar', $producto->id, 2)
            ->assertDispatched('carrito-actualizado')
            ->call('actualizar', $producto->id, 7)
            ->assertDispatched('toast', tipo: 'warning')
            ->call('quitar', $producto->id)
            ->assertSee('Tu carrito está vacío');

        $this->assertSame(0, $this->carrito->contar());
    }

    public function test_el_icono_se_actualiza_con_el_evento(): void
    {
        $producto = Producto::factory()->create(['stock' => 5]);

        $icono = Livewire::test(CarritoIcono::class)->assertSet('cantidad', 0);

        $this->carrito->agregar($producto->id, 2);

        $icono->dispatch('carrito-actualizado')->assertSet('cantidad', 2);
    }
}
