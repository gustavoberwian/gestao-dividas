<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoricoJurosControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_cadastrar_taxa_de_juros_dispara_o_recalculo_do_saldo_devedor(): void
    {
        $user = User::factory()->create();
        $divida = $user->dividas()->create([
            'descricao' => 'Emprestimo pessoal',
            'valor_principal' => 1000,
            'credor' => 'Banco X',
            'data_inicio' => '2026-01-01',
            'saldo_devedor_atual' => 1000,
        ]);

        $response = $this->actingAs($user)->post("/dividas/{$divida->id}/juros", [
            'mes_referencia' => '2026-01-01',
            'taxa_juros' => 0.02,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('historico_juros', [
            'divida_id' => $divida->id,
            'mes_referencia' => '2026-01-01',
            'saldo_devedor_calculado' => '1020.00',
        ]);
        $this->assertEqualsWithDelta(1020.00, (float) $divida->fresh()->saldo_devedor_atual, 0.001);
    }

    public function test_usuario_nao_pode_lancar_juros_em_divida_de_outro_usuario(): void
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

        $response = $this->actingAs($outroUsuario)->post("/dividas/{$divida->id}/juros", [
            'mes_referencia' => '2026-01-01',
            'taxa_juros' => 0.02,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('historico_juros', ['divida_id' => $divida->id]);
    }
}
