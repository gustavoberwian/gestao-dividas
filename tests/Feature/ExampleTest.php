<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A raiz redireciona para login (visitante) ou dashboard (autenticado).
     */
    public function test_a_raiz_redireciona_visitante_para_o_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    public function test_a_raiz_redireciona_usuario_autenticado_para_o_dashboard(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/');

        $response->assertRedirect('/dashboard');
    }
}
