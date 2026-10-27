<?php

namespace Tests\Feature;

use App\Exceptions\TipoCambioNoDisponible;
use App\Livewire\Admin\Productos\Formulario;
use App\Models\User;
use App\Services\TipoCambioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Livewire\Livewire;
use Tests\TestCase;

class TipoCambioTest extends TestCase
{
    use RefreshDatabase;

    private const BANXICO = 'www.banxico.org.mx/*';

    private const FRANKFURTER = 'api.frankfurter.dev/*';

    protected function setUp(): void
    {
        parent::setUp();

        Sleep::fake();
        config(['services.banxico.token' => 'token-de-prueba']);
    }

    private function respuestaBanxico(string $dato = '18.5798'): array
    {
        return ['bmx' => ['series' => [['idSerie' => 'SF43718', 'datos' => [['fecha' => '26/10/2026', 'dato' => $dato]]]]]];
    }

    private function respuestaFrankfurter(): array
    {
        return ['amount' => 1.0, 'base' => 'USD', 'date' => '2026-10-27', 'rates' => ['MXN' => 18.6368]];
    }

    public function test_dos_lecturas_hacen_una_sola_llamada_externa(): void
    {
        Http::fake([self::BANXICO => Http::response($this->respuestaBanxico())]);

        $servicio = app(TipoCambioService::class);
        $primera = $servicio->obtener();
        $segunda = $servicio->obtener();

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->hasHeader('Bmx-Token', 'token-de-prueba'));
        $this->assertSame(['valor' => 18.5798, 'fecha' => '2026-10-26', 'fuente' => 'Banxico FIX'], $primera);
        $this->assertSame($primera, $segunda);
        $this->assertSame($primera, Cache::get(TipoCambioService::LLAVE_ULTIMO));
    }

    public function test_usa_frankfurter_si_banxico_falla(): void
    {
        Http::fake([
            self::BANXICO => Http::response('Error', 500),
            self::FRANKFURTER => Http::response($this->respuestaFrankfurter()),
        ]);

        $tipoCambio = app(TipoCambioService::class)->obtener();

        $this->assertSame(18.6368, $tipoCambio['valor']);
        $this->assertSame('Frankfurter (BCE)', $tipoCambio['fuente']);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'banxico'));
    }

    public function test_usa_frankfurter_si_banxico_aun_no_publica_el_dato(): void
    {
        Http::fake([
            self::BANXICO => Http::response($this->respuestaBanxico('N/E')),
            self::FRANKFURTER => Http::response($this->respuestaFrankfurter()),
        ]);

        $this->assertSame('Frankfurter (BCE)', app(TipoCambioService::class)->obtener()['fuente']);
    }

    public function test_sin_token_va_directo_a_frankfurter(): void
    {
        config(['services.banxico.token' => null]);
        Http::fake([self::FRANKFURTER => Http::response($this->respuestaFrankfurter())]);

        app(TipoCambioService::class)->obtener();

        Http::assertSentCount(1);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'banxico'));
    }

    public function test_usa_el_ultimo_valor_guardado_si_ambas_fallan(): void
    {
        $ultimo = ['valor' => 18.1234, 'fecha' => '2026-10-20', 'fuente' => 'Banxico FIX'];
        Cache::forever(TipoCambioService::LLAVE_ULTIMO, $ultimo);
        Http::fake(['*' => Http::response('Error', 503)]);

        $this->assertSame($ultimo, app(TipoCambioService::class)->obtener());
    }

    public function test_sin_respuesta_ni_valor_previo_lanza_excepcion(): void
    {
        Http::fake(['*' => Http::failedConnection()]);

        $this->expectException(TipoCambioNoDisponible::class);

        app(TipoCambioService::class)->obtener();
    }

    public function test_el_formulario_recalcula_el_precio_mxn_con_el_tipo_de_cambio(): void
    {
        $this->actingAs(User::factory()->make());
        Http::fake([self::BANXICO => Http::response($this->respuestaBanxico())]);

        Livewire::test(Formulario::class)
            ->set('form.precio_usd', '10')
            ->call('recalcularMxn')
            ->assertHasNoErrors()
            ->assertSet('form.precio_mxn', '185.80')
            ->assertSee('Banxico FIX');
    }

    public function test_recalcular_exige_un_precio_usd_valido(): void
    {
        $this->actingAs(User::factory()->make());
        Http::fake();

        Livewire::test(Formulario::class)
            ->call('recalcularMxn')
            ->assertHasErrors(['form.precio_usd' => 'required'])
            ->assertSet('form.precio_mxn', '');

        Http::assertNothingSent();
    }
}
