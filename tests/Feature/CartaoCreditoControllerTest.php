<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartaoCreditoControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_autenticado_pode_cadastrar_cartao_de_credito(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/cartoes', [
            'nome_cartao' => 'Nubank',
            'limite' => 5000,
            'dia_fechamento' => 10,
            'dia_vencimento' => 17,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cartoes_credito', [
            'user_id' => $user->id,
            'nome_cartao' => 'Nubank',
            'dia_fechamento' => 10,
            'dia_vencimento' => 17,
        ]);
    }

    public function test_dia_fechamento_fora_do_intervalo_valido_e_rejeitado(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/cartoes', [
            'nome_cartao' => 'Nubank',
            'limite' => 5000,
            'dia_fechamento' => 40,
            'dia_vencimento' => 17,
        ]);

        $response->assertSessionHasErrors('dia_fechamento');
        $this->assertDatabaseMissing('cartoes_credito', ['nome_cartao' => 'Nubank']);
    }

    public function test_usuario_nao_autenticado_nao_pode_cadastrar_cartao(): void
    {
        $response = $this->post('/cartoes', [
            'nome_cartao' => 'Nubank',
            'limite' => 5000,
            'dia_fechamento' => 10,
            'dia_vencimento' => 17,
        ]);

        $response->assertRedirect('/login');
    }
}
