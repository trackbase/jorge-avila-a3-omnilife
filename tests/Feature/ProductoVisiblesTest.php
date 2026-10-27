<?php

namespace Tests\Feature;

use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductoVisiblesTest extends TestCase
{
    use RefreshDatabase;

    public function test_solo_incluye_activos_vigentes_y_no_eliminados(): void
    {
        $visible = Producto::factory()->create();
        $vigenteHoy = Producto::factory()->create(['fecha_vigencia' => today()->toDateString()]);
        Producto::factory()->inactivo()->create();
        Producto::factory()->vencido()->create();
        Producto::factory()->create()->delete();

        $this->assertEqualsCanonicalizing(
            [$visible->id, $vigenteHoy->id],
            Producto::visibles()->pluck('id')->all()
        );
    }
}
