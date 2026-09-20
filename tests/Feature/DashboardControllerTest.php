<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_saldo_disponivel_desconta_faturas_abertas_e_parcelas_de_dividas_do_mes(): void
    {
        $user = User::factory()->create(['renda_mensal' => 5000]);
        $hoje = Carbon::now();

        $cartao = $user->cartoesCredito()->create([
            'nome_cartao' => 'Nubank',
            'limite' => 5000,
            'dia_fechamento' => 10,
            'dia_vencimento' => 17,
        ]);
        // fatura aberta: entra no calculo
        $cartao->faturas()->create([
            'mes_referencia' => $hoje->copy()->startOfMonth(),
            'valor_total' => 300,
            'status_pagamento' => 'aberta',
        ]);
        // fatura ja paga: nao deve entrar no calculo
        $cartao->faturas()->create([
            'mes_referencia' => $hoje->copy()->subMonth()->startOfMonth(),
            'valor_total' => 999,
            'status_pagamento' => 'paga',
        ]);

        $divida = $user->dividas()->create([
            'descricao' => 'Emprestimo pessoal',
            'valor_principal' => 1000,
            'credor' => 'Banco X',
            'data_inicio' => '2026-01-01',
            'saldo_devedor_atual' => 1000,
        ]);
        // parcela pendente no mes corrente: entra no calculo
        $divida->parcelas()->create([
            'numero_parcela' => 1,
            'valor' => 200,
            'data_vencimento' => $hoje->copy()->startOfMonth()->addDays(5),
            'status_pagamento' => 'pendente',
        ]);
        // parcela ja paga: nao deve entrar no calculo
        $divida->parcelas()->create([
            'numero_parcela' => 2,
            'valor' => 150,
            'data_vencimento' => $hoje->copy()->startOfMonth()->addDays(6),
            'status_pagamento' => 'paga',
        ]);
        // parcela pendente de outro mes: nao deve entrar no calculo
        $divida->parcelas()->create([
            'numero_parcela' => 3,
            'valor' => 400,
            'data_vencimento' => $hoje->copy()->addMonth()->startOfMonth(),
            'status_pagamento' => 'pendente',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        // 5000 (renda) - 300 (fatura aberta) - 200 (parcela pendente do mes) = 4500
        $response->assertSee('4.500,00');
    }

    public function test_usuario_sem_renda_cadastrada_nao_gera_erro(): void
    {
        $user = User::factory()->create(['renda_mensal' => null]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
    }
}
