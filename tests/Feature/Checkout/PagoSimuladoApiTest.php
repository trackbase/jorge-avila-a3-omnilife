<?php

namespace Tests\Feature\Checkout;

use Tests\TestCase;

class PagoSimuladoApiTest extends TestCase
{
    private function pagar(string $tarjeta)
    {
        return $this->postJson('/api/pagos/simular', [
            'numero_tarjeta' => $tarjeta,
            'monto' => '1160.00',
            'referencia' => 'PED-2026-000001',
        ]);
    }

    public function test_la_tarjeta_4242_se_aprueba_con_referencia(): void
    {
        $this->pagar('4242424242424242')
            ->assertOk()
            ->assertJson(['estatus' => 'aprobado', 'ultimos4' => '4242'])
            ->assertJsonPath('referencia', fn ($referencia) => str_starts_with($referencia, 'SIM-'));
    }

    public function test_la_tarjeta_0002_se_rechaza(): void
    {
        $this->pagar('4000000000000002')
            ->assertStatus(402)
            ->assertJson(['estatus' => 'rechazado', 'ultimos4' => '0002'])
            ->assertJsonMissingPath('referencia');
    }

    public function test_cualquier_otra_tarjeta_es_invalida(): void
    {
        $this->pagar('5555555555554444')
            ->assertStatus(422)
            ->assertJson(['estatus' => 'invalida', 'mensaje' => 'Tarjeta inválida.']);
    }

    public function test_sin_datos_responde_422_con_errores_aunque_no_pida_json(): void
    {
        $this->post('/api/pagos/simular')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['numero_tarjeta', 'monto', 'referencia']);
    }

    public function test_nunca_devuelve_el_numero_completo(): void
    {
        $this->assertStringNotContainsString('4242424242424242', $this->pagar('4242424242424242')->getContent());
    }
}
