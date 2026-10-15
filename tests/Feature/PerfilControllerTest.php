<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerfilControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_pode_atualizar_a_propria_renda_mensal(): void
    {
        $user = User::factory()->create(['renda_mensal' => null]);

        $response = $this->actingAs($user)->put('/perfil/renda', [
            'renda_mensal' => 5000,
        ]);

        $response->assertRedirect();
        $this->assertEqualsWithDelta(5000.00, (float) $user->fresh()->renda_mensal, 0.001);
    }

    // A1 / RF002: o formulario de renda aparece no dashboard
    public function test_dashboard_exibe_formulario_de_renda_mensal(): void
    {
        $user = User::factory()->create(['renda_mensal' => null]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('action="'.route('perfil.renda.atualizar').'"', false);
        $response->assertSee('name="renda_mensal"', false);
        $response->assertSee('name="_method" value="PUT"', false);
    }

    // A1 / RF002: salvar pela tela (formato brasileiro) atualiza users.renda_mensal e mostra o status
    public function test_salvar_renda_com_virgula_atualiza_e_mostra_mensagem(): void
    {
        $user = User::factory()->create(['renda_mensal' => null]);

        $response = $this->actingAs($user)
            ->from('/dashboard')
            ->put('/perfil/renda', ['renda_mensal' => '5.250,75']);

        $response->assertRedirect('/dashboard');
        $this->assertEqualsWithDelta(5250.75, (float) $user->fresh()->renda_mensal, 0.001);

        $this->get('/dashboard')
            ->assertSee('Renda mensal atualizada com sucesso.')
            ->assertSee('5.250,75');
    }

    public function test_renda_com_mais_de_duas_casas_e_rejeitada(): void
    {
        $user = User::factory()->create(['renda_mensal' => null]);

        $response = $this->actingAs($user)->put('/perfil/renda', [
            'renda_mensal' => '100,555',
        ]);

        $response->assertSessionHasErrors('renda_mensal');
        $this->assertNull($user->fresh()->renda_mensal);
    }

    public function test_renda_mensal_negativa_e_rejeitada(): void
    {
        $user = User::factory()->create(['renda_mensal' => null]);

        $response = $this->actingAs($user)->put('/perfil/renda', [
            'renda_mensal' => -100,
        ]);

        $response->assertSessionHasErrors('renda_mensal');
        $this->assertNull($user->fresh()->renda_mensal);
    }
}
