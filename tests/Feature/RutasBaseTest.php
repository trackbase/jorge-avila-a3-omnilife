<?php

namespace Tests\Feature;

use Tests\TestCase;

class RutasBaseTest extends TestCase
{
    public function test_la_tienda_responde(): void
    {
        $this->get('/')->assertOk()->assertSee('Catálogo');
    }

    public function test_el_login_de_admin_responde(): void
    {
        $this->get('/admin/login')->assertOk()->assertSee('Acceso administrativo');
    }
}
