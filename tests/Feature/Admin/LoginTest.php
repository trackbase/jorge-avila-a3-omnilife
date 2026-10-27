<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['email' => 'admin@tienda.test', 'password' => 'Secreta123']);
    }

    public function test_admin_sin_sesion_redirige_al_login(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_login_correcto_entra_al_panel(): void
    {
        Livewire::test(Login::class)
            ->set('email', 'admin@tienda.test')
            ->set('password', 'Secreta123')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($this->admin);

        Http::fake();
        $this->get('/admin')->assertOk()->assertSee('Panel de administración');
    }

    public function test_login_incorrecto_muestra_error(): void
    {
        Livewire::test(Login::class)
            ->set('email', 'admin@tienda.test')
            ->set('password', 'incorrecta')
            ->call('login')
            ->assertHasErrors('email')
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_valida_campos_requeridos_y_formato_de_correo(): void
    {
        Livewire::test(Login::class)
            ->call('login')
            ->assertHasErrors(['email' => 'required', 'password' => 'required']);

        Livewire::test(Login::class)
            ->set('email', 'no-es-correo')
            ->assertHasErrors(['email' => 'email']);
    }

    public function test_bloquea_el_sexto_intento_aunque_la_contrasena_sea_correcta(): void
    {
        $componente = Livewire::test(Login::class)
            ->set('email', 'admin@tienda.test')
            ->set('password', 'incorrecta');

        foreach (range(1, 5) as $intento) {
            $componente->call('login');
        }

        $componente->set('password', 'Secreta123')
            ->call('login')
            ->assertHasErrors('email')
            ->assertSee('Demasiados intentos')
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_el_bloqueo_no_se_evade_con_variantes_unicode_del_correo(): void
    {
        $componente = Livewire::test(Login::class)
            ->set('email', 'admin@tienda.test')
            ->set('password', 'incorrecta');

        foreach (range(1, 5) as $intento) {
            $componente->call('login');
        }

        // "ａ" de ancho completo: MySQL (utf8mb4_unicode_ci) la compara igual que "a".
        Livewire::test(Login::class)
            ->set('email', "\u{FF41}dmin@tienda.test")
            ->set('password', 'Secreta123')
            ->call('login')
            ->assertHasErrors('email')
            ->assertSee('Demasiados intentos');

        $this->assertGuest();
    }

    public function test_cerrar_sesion(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
