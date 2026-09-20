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

    public function test_evolucao_do_saldo_devedor_soma_todas_as_dividas_do_usuario_por_mes(): void
    {
        $user = User::factory()->create();

        $divida1 = $user->dividas()->create([
            'descricao' => 'Divida 1', 'valor_principal' => 1000, 'credor' => 'Banco X',
            'data_inicio' => '2026-01-01', 'saldo_devedor_atual' => 1000,
        ]);
        $divida2 = $user->dividas()->create([
            'descricao' => 'Divida 2', 'valor_principal' => 500, 'credor' => 'Banco Y',
            'data_inicio' => '2026-01-01', 'saldo_devedor_atual' => 500,
        ]);

        $divida1->historicoJuros()->create(['mes_referencia' => '2026-01-01', 'taxa_juros' => 0, 'saldo_devedor_calculado' => 1000]);
        $divida2->historicoJuros()->create(['mes_referencia' => '2026-01-01', 'taxa_juros' => 0, 'saldo_devedor_calculado' => 500]);
        $divida1->historicoJuros()->create(['mes_referencia' => '2026-02-01', 'taxa_juros' => 0, 'saldo_devedor_calculado' => 800]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertViewHas('evolucaoSaldoDevedorLabels', function ($labels) {
            return $labels->values()->all() === ['01/2026', '02/2026'];
        });
        $response->assertViewHas('evolucaoSaldoDevedorValores', function ($valores) {
            return $valores->values()->all() === [1500.0, 800.0];
        });
    }

    public function test_evolucao_do_saldo_devedor_nao_mistura_dados_de_outro_usuario(): void
    {
        $user = User::factory()->create();
        $outroUsuario = User::factory()->create();

        $divida = $outroUsuario->dividas()->create([
            'descricao' => 'Divida de outro usuario', 'valor_principal' => 9999, 'credor' => 'Banco Z',
            'data_inicio' => '2026-01-01', 'saldo_devedor_atual' => 9999,
        ]);
        $divida->historicoJuros()->create(['mes_referencia' => '2026-01-01', 'taxa_juros' => 0, 'saldo_devedor_calculado' => 9999]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertViewHas('evolucaoSaldoDevedorValores', function ($valores) {
            return $valores->isEmpty();
        });
    }
}
