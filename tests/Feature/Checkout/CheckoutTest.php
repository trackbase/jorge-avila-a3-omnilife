<?php

namespace Tests\Feature\Checkout;

use App\Enums\EstatusPedido;
use App\Livewire\Tienda\Checkout;
use App\Models\Pedido;
use App\Models\Producto;
use App\Services\CarritoService;
use App\Services\TipoCambioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private const PASARELA = '*/api/pagos/simular';

    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::put(TipoCambioService::LLAVE, ['valor' => 18.5, 'fecha' => '2026-10-27', 'fuente' => 'Prueba'], 60);

        $this->producto = Producto::factory()->create([
            'sku' => 'CHK-001',
            'nombre' => 'Batido Checkout',
            'precio_mxn' => 100.00,
            'precio_usd' => 5.50,
            'stock' => 5,
        ]);
        app(CarritoService::class)->agregar($this->producto->id, 2);
    }

    private function bancoResponde(string $estatus, int $codigo = 200): void
    {
        Http::fake([self::PASARELA => Http::response([
            'estatus' => $estatus,
            'referencia' => $estatus === 'aprobado' ? 'SIM-PRUEBA123' : null,
            'mensaje' => $estatus === 'aprobado' ? 'Pago aprobado.' : 'El banco rechazó el pago: fondos insuficientes.',
        ], $codigo)]);
    }

    private function checkoutLleno(string $tarjeta = '4242424242424242'): Testable
    {
        return Livewire::test(Checkout::class)
            ->set('correo', 'cliente@ejemplo.com')
            ->set('titular', 'Cliente Prueba')
            ->set('numeroTarjeta', $tarjeta)
            ->set('vencimiento', now()->addYear()->format('m/y'))
            ->set('cvv', '123');
    }

    public function test_muestra_productos_subtotal_iva_y_totales_en_mxn_y_usd(): void
    {
        $this->get(route('tienda.checkout'))
            ->assertOk()
            ->assertSee('2 × Batido Checkout')
            ->assertSeeInOrder(['Subtotal', '$200.00', '$11.00'])
            ->assertSeeInOrder(['IVA (16 %)', '$32.00', '$1.76'])
            ->assertSeeInOrder(['Total', '$232.00', '$12.76']);
    }

    public function test_con_el_carrito_vacio_regresa_al_carrito(): void
    {
        app(CarritoService::class)->vaciar();

        $this->get(route('tienda.checkout'))->assertRedirect(route('tienda.carrito'));
    }

    public function test_el_correo_es_requerido_y_valido_antes_de_generar_el_pedido(): void
    {
        Http::fake();

        $this->checkoutLleno()->set('correo', '')->call('pagar')->assertHasErrors(['correo' => 'required']);
        $this->checkoutLleno()->set('correo', 'no-es-correo')->call('pagar')->assertHasErrors(['correo' => 'email']);

        $this->assertSame(0, Pedido::count());
        Http::assertNothingSent();
    }

    public function test_valida_los_datos_de_la_tarjeta_en_el_servidor(): void
    {
        Http::fake();

        $this->checkoutLleno('42')
            ->set('vencimiento', '01/20')
            ->set('cvv', '1')
            ->call('pagar')
            ->assertHasErrors(['numeroTarjeta' => 'digits_between', 'vencimiento', 'cvv' => 'digits_between'])
            ->assertSee('La tarjeta está vencida.');

        Http::assertNothingSent();
    }

    public function test_pago_aprobado_genera_pedido_pagado_con_folio_y_descuenta_stock(): void
    {
        $this->bancoResponde('aprobado');

        $this->checkoutLleno()
            ->call('pagar')
            ->assertHasNoErrors()
            ->assertDispatched('carrito-actualizado')
            ->assertSee('¡Gracias por tu compra!')
            ->assertSet('numeroTarjeta', '')
            ->assertSet('cvv', '');

        $pedido = Pedido::with('detalles')->sole();
        $this->assertSame(EstatusPedido::Pagado, $pedido->estatus);
        $this->assertMatchesRegularExpression('/^PED-\d{4}-\d{6}$/', $pedido->folio);
        $this->assertSame(['232.00', '32.00', '12.76', '18.5000'], [$pedido->total, $pedido->impuestos, $pedido->total_usd, $pedido->tipo_cambio]);
        $this->assertSame(['CHK-001', 'Batido Checkout', 2], [$pedido->detalles[0]->sku, $pedido->detalles[0]->nombre, $pedido->detalles[0]->cantidad]);
        $this->assertSame('SIM-PRUEBA123', $pedido->referencia_pago);
        $this->assertSame(3, $this->producto->refresh()->stock);
        $this->assertSame(0, app(CarritoService::class)->contar());

        Http::assertSent(fn ($request) => $request['referencia'] === $pedido->folio && $request['monto'] === '232.00');
    }

    public function test_pago_rechazado_deja_el_stock_intacto_y_el_pedido_rechazado(): void
    {
        $this->bancoResponde('rechazado', 402);

        $this->checkoutLleno('4000000000000002')
            ->call('pagar')
            ->assertHasErrors('pago')
            ->assertSee('fondos insuficientes');

        $pedido = Pedido::sole();
        $this->assertSame(EstatusPedido::Rechazado, $pedido->estatus);
        $this->assertSame('0002', $pedido->tarjeta_ultimos4);
        $this->assertSame(5, $this->producto->refresh()->stock);
        $this->assertSame(2, app(CarritoService::class)->contar());
    }

    public function test_stock_insuficiente_al_confirmar_da_error_y_no_crea_pedido(): void
    {
        Http::fake();
        $checkout = $this->checkoutLleno();

        // El stock baja entre el último render (que mostró 2 piezas) y el clic en "Pagar".
        $this->producto->update(['stock' => 1]);

        $checkout->call('pagar')
            ->assertHasErrors('pago')
            ->assertSee('Ya no hay suficiente stock de Batido Checkout');

        $this->assertSame(0, Pedido::count());
        $this->assertSame(1, $this->producto->refresh()->stock);
        Http::assertNothingSent();
    }

    public function test_si_el_stock_se_agota_mientras_se_cobra_el_pedido_queda_cancelado(): void
    {
        Http::fake([self::PASARELA => function () {
            // Otro cliente compra todo justo mientras el banco responde.
            $this->producto->update(['stock' => 0]);

            return Http::response(['estatus' => 'aprobado', 'referencia' => 'SIM-CARRERA', 'mensaje' => 'Pago aprobado.']);
        }]);

        $this->checkoutLleno()->call('pagar')->assertHasErrors('pago')->assertSee('se reembolsará');

        $this->assertSame(EstatusPedido::Cancelado, Pedido::sole()->estatus);
        $this->assertSame(0, $this->producto->refresh()->stock);
    }

    public function test_nunca_se_guarda_el_numero_completo_de_la_tarjeta(): void
    {
        $this->bancoResponde('aprobado');

        $this->checkoutLleno()->call('pagar');

        $this->assertSame('4242', Pedido::sole()->tarjeta_ultimos4);
        $this->assertStringNotContainsString('4242424242424242', json_encode(Pedido::with('detalles')->get()->toArray()));
        $this->assertStringNotContainsString('4242424242424242', json_encode(session()->all()));
    }
}
