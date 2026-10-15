<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Bloco J: alertas, barra "Para onde vai sua renda", proximos vencimentos e a
 * decomposicao juros x amortizacao do relatorio.
 */
class DashboardPainelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-15 10:00', 'America/Sao_Paulo'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function cenario(float $renda): User
    {
        $user = User::factory()->create(['renda_mensal' => $renda]);

        $cartao = $user->cartoesCredito()->create([
            'nome_cartao' => 'Nubank', 'limite' => 5000, 'dia_fechamento' => 13, 'dia_vencimento' => 20,
        ]);
        // vence em 20/10 -> daqui a 5 dias
        $cartao->faturas()->create(['mes_referencia' => '2026-10-01', 'valor_total' => 1200, 'status_pagamento' => 'aberta']);

        $divida = $user->dividas()->create([
            'descricao' => 'Empréstimo', 'valor_principal' => 10000, 'credor' => 'Banco X',
            'data_inicio' => '2026-01-01', 'saldo_devedor_atual' => 10000,
        ]);
        $divida->parcelas()->create(['numero_parcela' => 1, 'valor' => 300, 'data_vencimento' => '2026-09-10', 'status_pagamento' => 'pendente']);
        $divida->parcelas()->create(['numero_parcela' => 2, 'valor' => 800, 'data_vencimento' => '2026-10-25', 'status_pagamento' => 'pendente']);
        $divida->parcelas()->create(['numero_parcela' => 3, 'valor' => 800, 'data_vencimento' => '2026-12-25', 'status_pagamento' => 'pendente']);

        return $user;
    }

    public function test_cartoes_alertas_e_proximos_vencimentos(): void
    {
        $response = $this->actingAs($this->cenario(5000))->get('/dashboard');

        $response->assertOk();
        // RLR = 5000 - 1200 - 800 (a vencida de setembro nao entra)
        $response->assertViewHas('saldoDisponivel', 3000.0);
        $response->assertSee('40% da renda comprometida');
        $response->assertSee('1 parcela vencida');
        $response->assertSee('R$ 300,00', false);
        $response->assertSeeInOrder(['Fatura do cartão', 'Nubank', 'vence em 5 dias']);

        // proximos 30 dias: vencida (10/09), fatura (20/10), parcela (25/10); a de dezembro fica fora
        $response->assertViewHas('proximosVencimentos', function ($itens) {
            return $itens->pluck('tipo')->all() === ['Parcela', 'Fatura', 'Parcela']
                && $itens->pluck('dias')->all() === [-35, 5, 10];
        });
        $response->assertSeeInOrder(['Vencida', 'Em 5 dias', 'Em 10 dias']);
    }

    public function test_barra_mostra_excedente_quando_comprometido_passa_da_renda(): void
    {
        $response = $this->actingAs($this->cenario(1500))->get('/dashboard');

        // comprometido 2000 > renda 1500: faturas 1200 + parcelas 300 dentro, 500 acima
        $response->assertViewHas('barraRenda', function ($barra) {
            $valores = collect($barra['segmentos'])->pluck('valor', 'rotulo')->all();

            return $valores === ['Faturas' => 1200.0, 'Parcelas' => 300.0, 'Acima da renda' => 500.0]
                && $barra['excedente'] === 500.0;
        });
        $response->assertSee('Comprometido acima da renda em R$ 500,00');
        $response->assertSee('133% da renda comprometida');
    }

    public function test_sem_renda_mostra_alerta_com_botao_e_sem_dividas_mostra_estado_vazio(): void
    {
        $user = User::factory()->create(['renda_mensal' => null]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee('Renda mensal não cadastrada');
        $response->assertSee('data-bs-target="#modalRenda"', false);
        $response->assertSee('Cadastrar dívida');
        $response->assertSee(route('dividas.index').'#modalNovaDivida', false);
    }

    public function test_relatorio_decompoe_pagamento_em_juros_e_amortizacao(): void
    {
        $user = User::factory()->create();
        $divida = $user->dividas()->create([
            'descricao' => 'C1', 'valor_principal' => 10000, 'credor' => 'Banco X',
            'data_inicio' => '2026-01-01', 'saldo_devedor_atual' => 10000,
        ]);
        foreach ([['2026-01-01', 0.015, 9150], ['2026-02-01', 0.012, 8259.80]] as $i => [$mes, $taxa, $saldo]) {
            $divida->historicoJuros()->create(['mes_referencia' => $mes, 'taxa_juros' => $taxa, 'saldo_devedor_calculado' => $saldo]);
            $divida->parcelas()->create(['numero_parcela' => $i + 1, 'valor' => 1000, 'data_vencimento' => substr($mes, 0, 8).'10', 'status_pagamento' => 'paga']);
        }

        $response = $this->actingAs($user)->get(route('dividas.relatorio', $divida));

        // mes 1: juros 10000 x 1,5% = 150; amortizacao 850. mes 2: juros 9150 x 1,2% = 109,80; amortizacao 890,20
        $response->assertViewHas('decomposicao', function ($linhas) {
            $linhas = array_values($linhas);

            return $linhas[0] === ['mes' => '01/2026', 'juros' => 150.0, 'pago' => 1000.0, 'amortizacao' => 850.0]
                && $linhas[1] === ['mes' => '02/2026', 'juros' => 109.8, 'pago' => 1000.0, 'amortizacao' => 890.2];
        });
        $response->assertSee('Quanto do que você pagou foi só juros');
    }
}
