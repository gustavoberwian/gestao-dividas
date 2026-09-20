<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParcelaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_marcar_parcela_de_divida_como_paga_dispara_recalculo_do_saldo_devedor(): void
    {
        $user = User::factory()->create();
        $divida = $user->dividas()->create([
            'descricao' => 'Emprestimo pessoal',
            'valor_principal' => 1000,
            'credor' => 'Banco X',
            'data_inicio' => '2026-01-01',
            'saldo_devedor_atual' => 1000,
        ]);
        $parcela = $divida->parcelas()->create([
            'numero_parcela' => 1,
            'valor' => 200,
            'data_vencimento' => '2026-01-15',
            'status_pagamento' => 'pendente',
        ]);

        $response = $this->actingAs($user)->patch("/parcelas/{$parcela->id}/pagar");

        $response->assertRedirect();
        $this->assertSame('paga', $parcela->fresh()->status_pagamento);

        // sem taxa cadastrada para o mes, o recalculo assume 0: 1000 - 200 = 800.00
        $this->assertEqualsWithDelta(800.00, (float) $divida->fresh()->saldo_devedor_atual, 0.001);
    }

    public function test_marcar_parcela_de_fatura_como_paga_nao_mexe_no_saldo_devedor_de_divida(): void
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
            'valor_total' => 300,
            'status_pagamento' => 'aberta',
        ]);
        $parcela = $fatura->parcelas()->create([
            'numero_parcela' => 1,
            'valor' => 300,
            'data_vencimento' => '2026-01-17',
            'status_pagamento' => 'pendente',
        ]);

        $response = $this->actingAs($user)->patch("/parcelas/{$parcela->id}/pagar");

        $response->assertRedirect();
        $this->assertSame('paga', $parcela->fresh()->status_pagamento);
    }

    public function test_usuario_nao_pode_marcar_parcela_de_divida_de_outro_usuario_como_paga(): void
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
        $parcela = $divida->parcelas()->create([
            'numero_parcela' => 1,
            'valor' => 200,
            'data_vencimento' => '2026-01-15',
            'status_pagamento' => 'pendente',
        ]);

        $response = $this->actingAs($outroUsuario)->patch("/parcelas/{$parcela->id}/pagar");

        $response->assertForbidden();
        $this->assertSame('pendente', $parcela->fresh()->status_pagamento);
    }

    public function test_usuario_nao_pode_marcar_parcela_de_fatura_de_outro_usuario_como_paga(): void
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
            'valor_total' => 300,
            'status_pagamento' => 'aberta',
        ]);
        $parcela = $fatura->parcelas()->create([
            'numero_parcela' => 1,
            'valor' => 300,
            'data_vencimento' => '2026-01-17',
            'status_pagamento' => 'pendente',
        ]);

        $response = $this->actingAs($outroUsuario)->patch("/parcelas/{$parcela->id}/pagar");

        $response->assertForbidden();
        $this->assertSame('pendente', $parcela->fresh()->status_pagamento);
    }
}
