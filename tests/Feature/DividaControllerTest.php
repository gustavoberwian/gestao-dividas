<?php

namespace Tests\Feature;

use App\Models\Divida;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DividaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_autenticado_pode_cadastrar_divida(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/dividas', [
            'descricao' => 'Emprestimo pessoal',
            'valor_principal' => 1000,
            'credor' => 'Banco X',
            'data_inicio' => '2026-01-01',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('dividas', [
            'user_id' => $user->id,
            'descricao' => 'Emprestimo pessoal',
            'valor_principal' => 1000,
            'saldo_devedor_atual' => 1000,
        ]);
    }

    public function test_dono_da_divida_pode_ver_o_relatorio_de_amortizacao(): void
    {
        $user = User::factory()->create();
        $divida = $user->dividas()->create([
            'descricao' => 'Emprestimo pessoal',
            'valor_principal' => 1000,
            'credor' => 'Banco X',
            'data_inicio' => '2026-01-01',
            'saldo_devedor_atual' => 1000,
        ]);

        $response = $this->actingAs($user)->get("/dividas/{$divida->id}/relatorio");

        $response->assertOk();
        $response->assertSee('Emprestimo pessoal');
    }

    public function test_usuario_nao_pode_ver_relatorio_de_divida_de_outro_usuario(): void
    {
        $dono = User::factory()->create();
        $outroUsuario = User::factory()->create();

        $divida = $dono->dividas()->create([
            'descricao' => 'Emprestimo pessoal',
            'valor_principal' => 1000,
            'credor' => 'Banco X',
            'data_inicio' => '2026-01-01',
            'saldo_devedor_atual' => 1000,
        ]);

        $response = $this->actingAs($outroUsuario)->get("/dividas/{$divida->id}/relatorio");

        $response->assertForbidden();
    }
}
