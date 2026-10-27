<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Pedidos\Index;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use App\Services\ReporteService;
use App\Services\TipoCambioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class ReportesTest extends TestCase
{
    use RefreshDatabase;

    private ReporteService $reportes;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-27 12:00:00');
        Cache::put(TipoCambioService::LLAVE, ['valor' => 18.5798, 'fecha' => '2026-10-26', 'fuente' => 'Banxico FIX'], 60);
        $this->actingAs(User::factory()->create());
        $this->reportes = app(ReporteService::class);
    }

    private function venta(Producto $producto, int $cantidad, string $fecha, string $estado = 'pagado'): Pedido
    {
        $subtotal = $producto->precio_mxn * $cantidad;
        $pedido = Pedido::factory()->{$estado === 'pagado' ? 'state' : $estado}([])->create([
            'fecha' => $fecha,
            'subtotal' => $subtotal,
            'impuestos' => round($subtotal * 0.16, 2),
            'total' => round($subtotal * 1.16, 2),
        ]);
        $pedido->detalles()->create([
            'producto_id' => $producto->id,
            'sku' => $producto->sku,
            'nombre' => $producto->nombre,
            'cantidad' => $cantidad,
            'precio_usd' => $producto->precio_usd,
            'precio_mxn' => $producto->precio_mxn,
        ]);

        return $pedido;
    }

    public function test_las_rutas_de_reportes_responden_con_sesion(): void
    {
        $this->get(route('admin.dashboard'))->assertOk()->assertSee(['Ganancias por mes', 'Top 3 más vendidos', '18.5798']);
        $this->get(route('admin.pedidos.index'))->assertOk()->assertSeeLivewire(Index::class);
    }

    public function test_pedidos_se_filtran_por_rango_de_fechas(): void
    {
        $septiembre = Pedido::factory()->create(['folio' => 'PED-2026-000001', 'fecha' => '2026-09-15 10:00']);
        $octubre = Pedido::factory()->create(['folio' => 'PED-2026-000002', 'fecha' => '2026-10-20 23:59']);
        Pedido::factory()->create(['folio' => 'PED-2026-000003', 'fecha' => '2026-10-26 00:00']);

        Livewire::test(Index::class)
            ->set('desde', '2026-10-01')
            ->set('hasta', '2026-10-20')
            ->assertSee($octubre->folio)
            ->assertDontSee($septiembre->folio)
            ->assertDontSee('PED-2026-000003')
            ->call('limpiarFechas')
            ->assertSee(['PED-2026-000001', 'PED-2026-000003']);
    }

    public function test_un_rango_invertido_se_voltea_y_las_fechas_invalidas_se_ignoran(): void
    {
        [$desde, $hasta] = $this->reportes->rango('2026-10-31', '2026-10-01');
        $this->assertSame(['2026-10-01 00:00:00', '2026-10-31 23:59:59'], [$desde->toDateTimeString(), $hasta->toDateTimeString()]);

        $this->assertSame([null, null], $this->reportes->rango('no-es-fecha', ''));
        $this->assertSame([null, null], $this->reportes->rango('2026-02-31', '0202-10-01'));
    }

    public function test_el_detalle_del_pedido_se_abre_en_el_modal(): void
    {
        $producto = Producto::factory()->create(['sku' => 'MOD-001', 'nombre' => 'Producto Modal']);
        $pedido = $this->venta($producto, 2, '2026-10-10');

        Livewire::test(Index::class)
            ->call('verDetalle', $pedido->id)
            ->assertDispatched('abrir-detalle')
            ->assertSee(['MOD-001', 'Producto Modal', $pedido->folio]);
    }

    public function test_ganancias_por_mes_solo_de_pedidos_pagados_y_con_meses_en_cero(): void
    {
        $producto = Producto::factory()->create(['precio_mxn' => 100]);
        $this->venta($producto, 1, '2026-08-05');
        $this->venta($producto, 2, '2026-08-20');
        $this->venta($producto, 9, '2026-08-21', 'rechazado');
        $this->venta($producto, 9, '2026-08-22', 'pendiente');
        $this->venta($producto, 1, '2026-10-01');

        $datos = $this->reportes->gananciasPorPeriodo(Carbon::parse('2026-08-01'), Carbon::parse('2026-10-31')->endOfDay());

        $this->assertCount(3, $datos['etiquetas']);
        $this->assertStringStartsWith('ago', $datos['etiquetas'][0]);
        $this->assertSame([300.0, 0.0, 100.0], $datos['valores']);
    }

    public function test_ganancias_por_semana(): void
    {
        $producto = Producto::factory()->create(['precio_mxn' => 50]);
        $this->venta($producto, 1, '2026-10-12');
        $this->venta($producto, 3, '2026-10-21');

        $datos = $this->reportes->gananciasPorPeriodo(Carbon::parse('2026-10-12'), Carbon::parse('2026-10-25')->endOfDay(), 'semana');

        $this->assertSame([50.0, 150.0], $datos['valores']);
    }

    public function test_cambiar_el_rango_actualiza_la_grafica_con_un_evento(): void
    {
        $producto = Producto::factory()->create(['precio_mxn' => 100]);
        $this->venta($producto, 1, '2026-10-05');

        Livewire::test(Dashboard::class)
            ->assertSet('desde', '2026-05-01')
            ->set('agrupacion', 'semana')
            ->assertDispatched('grafica-actualizada')
            ->assertSee('Ganancias por semana')
            ->set('desde', '2026-10-01')
            ->assertDispatched('grafica-actualizada');
    }

    public function test_top_3_ordenado_por_unidades_e_incluye_eliminados(): void
    {
        $primero = Producto::factory()->create(['sku' => 'TOP-1']);
        $eliminado = Producto::factory()->create(['sku' => 'TOP-2']);
        $tercero = Producto::factory()->create(['sku' => 'TOP-3']);
        $cuarto = Producto::factory()->create(['sku' => 'TOP-4']);

        $this->venta($primero, 10, '2026-10-01');
        $this->venta($eliminado, 7, '2026-10-02');
        $this->venta($tercero, 4, '2026-10-03');
        $this->venta($cuarto, 2, '2026-10-04');
        $this->venta($cuarto, 50, '2026-10-05', 'rechazado');
        $eliminado->delete();

        $top = $this->reportes->topProductos(Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31')->endOfDay());

        $this->assertSame(['TOP-1', 'TOP-2', 'TOP-3'], $top->pluck('sku')->all());
        $this->assertEquals([10, 7, 4], $top->pluck('unidades')->all());
        $this->assertEquals([0, 1, 0], $top->pluck('eliminado')->all());

        Livewire::test(Dashboard::class)->set('desde', '2026-10-01')->assertSeeInOrder(['TOP-1', 'TOP-2', 'Eliminado', 'TOP-3']);
    }
}
