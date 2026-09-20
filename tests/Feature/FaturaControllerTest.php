<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaturaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_dono_do_cartao_pode_cadastrar_fatura(): void
    {
        $user = User::factory()->create();
        $cartao = $user->cartoesCredito()->create([
            'nome_cartao' => 'Nubank',
            'limite' => 5000,
            'dia_fechamento' => 10,
            'dia_vencimento' => 17,
        ]);

        $response = $this->actingAs($user)->post('/faturas', [
            'cartao_credito_id' => $cartao->id,
            'mes_referencia' => '2026-01-01',
            'valor_total' => 350.50,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('faturas', [
            'cartao_credito_id' => $cartao->id,
            'valor_total' => 350.50,
            'status_pagamento' => 'aberta',
        ]);
    }

    public function test_usuario_nao_pode_cadastrar_fatura_em_cartao_de_outro_usuario(): void
    {
        $dono = User::factory()->create();
        $outroUsuario = User::factory()->create();

        $cartao = $dono->cartoesCredito()->create([
            'nome_cartao' => 'Nubank',
            'limite' => 5000,
            'dia_fechamento' => 10,
            'dia_vencimento' => 17,
        ]);

        $response = $this->actingAs($outroUsuario)->post('/faturas', [
            'cartao_credito_id' => $cartao->id,
            'mes_referencia' => '2026-01-01',
            'valor_total' => 350.50,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('faturas', ['cartao_credito_id' => $cartao->id]);
    }

    public function test_dono_pode_marcar_fatura_como_paga(): void
    {
        $user = User::factory()->create();
        $cartao = $user->cartoesCredito()->create([
            'nome_cartao' => 'Nubank',
            'limite' => 5000,
            'dia_fechamento' => 10,
            'dia_vencimento' => 17,
        ]);
        $fatura = $cartao->faturas()->create([
            'mes_referencia' => '2026-01-01',
            'valor_total' => 350.50,
            'status_pagamento' => 'aberta',
        ]);

        $response = $this->actingAs($user)->patch("/faturas/{$fatura->id}/pagar");

        $response->assertRedirect();
        $this->assertSame('paga', $fatura->fresh()->status_pagamento);
    }

    public function test_usuario_nao_pode_marcar_fatura_de_outro_usuario_como_paga(): void
    {
        $dono = User::factory()->create();
        $outroUsuario = User::factory()->create();

        $cartao = $dono->cartoesCredito()->create([
            'nome_cartao' => 'Nubank',
            'limite' => 5000,
            'dia_fechamento' => 10,
            'dia_vencimento' => 17,
        ]);
        $fatura = $cartao->faturas()->create([
            'mes_referencia' => '2026-01-01',
            'valor_total' => 350.50,
            'status_pagamento' => 'aberta',
        ]);

        $response = $this->actingAs($outroUsuario)->patch("/faturas/{$fatura->id}/pagar");

        $response->assertForbidden();
        $this->assertSame('aberta', $fatura->fresh()->status_pagamento);
    }
}
