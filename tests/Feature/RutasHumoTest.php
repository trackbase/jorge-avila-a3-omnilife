<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\User;
use App\Services\CarritoService;
use App\Services\TipoCambioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/** Prueba de humo: todas las pantallas responden 200 (las del admin con sesión). */
class RutasHumoTest extends TestCase
{
    use RefreshDatabase;

    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::put(TipoCambioService::LLAVE, ['valor' => 18.5798, 'fecha' => '2026-10-26', 'fuente' => 'Banxico FIX'], 60);
        $this->producto = Producto::factory()->create();
    }

    public function test_las_rutas_publicas_responden(): void
    {
        $this->get(route('tienda.catalogo'))->assertOk();
        $this->get(route('tienda.producto', $this->producto->sku))->assertOk();
        $this->get(route('tienda.carrito'))->assertOk();
        $this->get(route('login'))->assertOk();

        app(CarritoService::class)->agregar($this->producto->id, 1);
        $this->get(route('tienda.checkout'))->assertOk();
    }

    public function test_las_rutas_del_admin_responden_con_sesion(): void
    {
        $this->actingAs(User::factory()->create());

        foreach ([
            route('admin.dashboard'),
            route('admin.productos.index'),
            route('admin.productos.crear'),
            route('admin.productos.detalle', $this->producto),
            route('admin.productos.editar', $this->producto),
            route('admin.pedidos.index'),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
