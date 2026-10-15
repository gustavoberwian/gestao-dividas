<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdentidadeVisualTest extends TestCase
{
    use RefreshDatabase;

    // Bloco B: titulo da aba = nome da pagina, sem marca do template
    public function test_tela_de_login_nao_exibe_marca_do_template(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('<title>Login</title>', false);
        $response->assertSee('Gestão de Dívidas');
        $this->assertDoesNotMatchRegularExpression('/vuexy|pixinvent/i', $response->getContent());
    }

    public function test_dashboard_tem_titulo_e_rodape_do_projeto(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/dashboard');

        $response->assertOk();
        $response->assertSee('<title>Dashboard</title>', false);
        $response->assertSee('Feito por Gustavo Berwian');
        $this->assertDoesNotMatchRegularExpression('/vuexy|pixinvent/i', $response->getContent());
    }
}
