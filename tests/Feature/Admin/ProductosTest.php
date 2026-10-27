<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Productos\Formulario;
use App\Livewire\Admin\Productos\Index;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class ProductosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
        Storage::fake('public');
    }

    private function datosValidos(array $cambios = []): array
    {
        return array_merge([
            'categoria_id' => Categoria::factory()->create()->id,
            'sku' => 'OMN-NEW-001',
            'nombre' => 'Batido Nuevo Fresa',
            'descripcion_corta' => 'Batido de prueba.',
            'descripcion_larga' => 'Descripción larga del batido de prueba.',
            'precio_usd' => '20.50',
            'precio_mxn' => '375.15',
            'stock' => '12',
            'fecha_vigencia' => now()->addMonth()->toDateString(),
            'activo' => true,
            'imagen' => UploadedFile::fake()->image('batido.png'),
        ], $cambios);
    }

    private function formularioCon(array $datos): Testable
    {
        $componente = Livewire::test(Formulario::class);
        foreach ($datos as $campo => $valor) {
            $componente->set("form.{$campo}", $valor);
        }

        return $componente;
    }

    public function test_las_rutas_del_crud_responden_con_sesion(): void
    {
        $producto = Producto::factory()->create();

        $this->get(route('admin.productos.index'))->assertOk()->assertSeeLivewire(Index::class);
        $this->get(route('admin.productos.crear'))->assertOk()->assertSee('Nuevo producto');
        $this->get(route('admin.productos.detalle', $producto))->assertOk()->assertSee($producto->sku);
        $this->get(route('admin.productos.editar', $producto))->assertOk()->assertSee('Editar '.$producto->sku);
    }

    public function test_el_listado_excluye_eliminados(): void
    {
        Producto::factory()->create(['sku' => 'VISIBLE-1']);
        Producto::factory()->create(['sku' => 'BORRADO-1'])->delete();

        Livewire::test(Index::class)->assertSee('VISIBLE-1')->assertDontSee('BORRADO-1');
    }

    public function test_busca_por_sku_y_por_nombre(): void
    {
        Producto::factory()->create(['sku' => 'ABC-001', 'nombre' => 'Batido Vainilla']);
        Producto::factory()->create(['sku' => 'XYZ-002', 'nombre' => 'Crema Facial']);

        Livewire::test(Index::class)
            ->set('buscar', 'ABC')
            ->assertSee('Batido Vainilla')->assertDontSee('Crema Facial')
            ->set('buscar', 'crema')
            ->assertSee('XYZ-002')->assertDontSee('ABC-001')
            ->set('buscar', 'no-existe')
            ->assertSee('No hay productos que coincidan');
    }

    public function test_pagina_de_diez_en_diez_y_reinicia_la_pagina_al_buscar(): void
    {
        foreach (range(1, 12) as $i) {
            Producto::factory()->create(['sku' => sprintf('PAG-%03d', $i)]);
        }

        Livewire::test(Index::class)
            ->call('ordenarPor', 'sku')
            ->assertSee('PAG-010')->assertDontSee('PAG-011')
            ->call('gotoPage', 2)
            ->assertSee('PAG-011')->assertDontSee('PAG-001')
            ->set('buscar', 'PAG')
            ->assertSee('PAG-001');
    }

    public function test_ordena_por_columna_permitida_e_ignora_las_demas(): void
    {
        Producto::factory()->create(['sku' => 'A-1', 'stock' => 50]);
        Producto::factory()->create(['sku' => 'B-1', 'stock' => 5]);

        Livewire::test(Index::class)
            ->call('ordenarPor', 'stock')
            ->assertSet('orden', 'stock')->assertSeeInOrder(['B-1', 'A-1'])
            ->call('ordenarPor', 'stock')
            ->assertSet('direccion', 'desc')->assertSeeInOrder(['A-1', 'B-1'])
            ->call('ordenarPor', 'password')
            ->assertSet('orden', 'stock');
    }

    public function test_alta_valida_guarda_el_producto_y_la_imagen(): void
    {
        $this->formularioCon($this->datosValidos())
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.productos.index'));

        $producto = Producto::where('sku', 'OMN-NEW-001')->firstOrFail();
        $this->assertSame('375.15', $producto->precio_mxn);
        Storage::disk('public')->assertExists($producto->imagen);
    }

    public function test_edicion_valida_conserva_su_sku_y_su_imagen(): void
    {
        $producto = Producto::factory()->create(['sku' => 'EDIT-001']);

        Livewire::test(Formulario::class, ['producto' => $producto])
            ->assertSet('form.sku', 'EDIT-001')
            ->set('form.nombre', 'Nombre Editado')
            ->set('form.stock', 3)
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.productos.index'));

        $producto->refresh();
        $this->assertSame('Nombre Editado', $producto->nombre);
        $this->assertSame(3, $producto->stock);
        $this->assertSame('productos/demo.svg', $producto->imagen);
    }

    public function test_sku_duplicado_aunque_el_original_este_eliminado(): void
    {
        Producto::factory()->create(['sku' => 'DUP-001'])->delete();

        $this->formularioCon($this->datosValidos(['sku' => 'DUP-001']))
            ->call('guardar')
            ->assertHasErrors(['form.sku' => 'unique']);

        $this->assertSame(1, Producto::withTrashed()->where('sku', 'DUP-001')->count());
    }

    public function test_precios_deben_ser_numericos_y_mayores_que_cero(): void
    {
        $this->formularioCon($this->datosValidos(['precio_usd' => '0', 'precio_mxn' => '-5']))
            ->call('guardar')
            ->assertHasErrors(['form.precio_usd' => 'gt', 'form.precio_mxn' => 'gt']);

        $this->formularioCon($this->datosValidos(['precio_usd' => 'abc']))
            ->call('guardar')
            ->assertHasErrors(['form.precio_usd' => 'numeric']);
    }

    public function test_todos_los_campos_son_requeridos(): void
    {
        Livewire::test(Formulario::class)
            ->call('guardar')
            ->assertHasErrors([
                'form.categoria_id' => 'required',
                'form.sku' => 'required',
                'form.nombre' => 'required',
                'form.descripcion_corta' => 'required',
                'form.descripcion_larga' => 'required',
                'form.precio_usd' => 'required',
                'form.precio_mxn' => 'required',
                'form.stock' => 'required',
                'form.fecha_vigencia' => 'required',
                'form.imagen' => 'required',
            ]);

        $this->assertSame(0, Producto::count());
    }

    public function test_nombre_con_acento_es_invalido_y_el_error_aparece_en_tiempo_real(): void
    {
        Livewire::test(Formulario::class)
            ->set('form.nombre', 'Proteína')
            ->assertHasErrors('form.nombre')
            ->assertSee('solo admite letras sin acentos')
            ->set('form.nombre', 'Proteina')
            ->assertHasNoErrors('form.nombre');
    }

    public function test_activar_e_inactivar(): void
    {
        $producto = Producto::factory()->create();

        $componente = Livewire::test(Index::class)->call('alternarActivo', $producto->id)->assertDispatched('toast');
        $this->assertFalse($producto->refresh()->activo);

        $componente->call('alternarActivo', $producto->id);
        $this->assertTrue($producto->refresh()->activo);
    }

    public function test_eliminar_hace_soft_delete(): void
    {
        $producto = Producto::factory()->create(['sku' => 'DEL-001']);

        Livewire::test(Index::class)
            ->call('eliminar', $producto->id)
            ->assertDispatched('toast')
            ->assertDontSee('DEL-001');

        $this->assertSoftDeleted($producto);
    }

    public function test_un_producto_eliminado_no_tiene_detalle_ni_edicion(): void
    {
        $producto = Producto::factory()->create();
        $producto->delete();

        $this->get(route('admin.productos.detalle', $producto))->assertNotFound();
        $this->get(route('admin.productos.editar', $producto))->assertNotFound();
    }
}
