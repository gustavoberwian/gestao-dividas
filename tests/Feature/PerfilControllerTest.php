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
