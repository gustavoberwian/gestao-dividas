<?php

namespace Tests\Unit\Services;

use App\Models\Divida;
use App\Models\HistoricoJuros;
use App\Models\User;
use App\Services\AmortizacaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AmortizacaoServiceTest extends TestCase
{
    use RefreshDatabase;

    private AmortizacaoService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AmortizacaoService();
    }

    private function criarDivida(float $valorPrincipal = 1000.00): Divida
    {
        $user = User::factory()->create();

        return $user->dividas()->create([
            'descricao' => 'Emprestimo de teste',
            'valor_principal' => $valorPrincipal,
            'credor' => 'Banco de Teste',
            'data_inicio' => '2026-01-01',
            'saldo_devedor_atual' => $valorPrincipal,
        ]);
    }

    public function test_primeiro_calculo_usa_valor_principal_como_saldo_anterior(): void
    {
        $divida = $this->criarDivida(1000.00);

        HistoricoJuros::create([
            'divida_id' => $divida->id,
            'mes_referencia' => '2026-01-01',
            'taxa_juros' => 0.02,
            'saldo_devedor_calculado' => 0,
        ]);

        $sd = $this->service->calcularSaldoDevedor($divida, '2026-01-01');

        // 1000 * 1.02 - 0 = 1020.00
        $this->assertEqualsWithDelta(1020.00, $sd, 0.001);
    }

    public function test_mes_seguinte_usa_saldo_do_historico_anterior_como_base(): void
    {
        $divida = $this->criarDivida(1000.00);

        HistoricoJuros::create([
            'divida_id' => $divida->id,
            'mes_referencia' => '2026-01-01',
            'taxa_juros' => 0.02,
            'saldo_devedor_calculado' => 1020.00,
        ]);
        HistoricoJuros::create([
            'divida_id' => $divida->id,
            'mes_referencia' => '2026-02-01',
            'taxa_juros' => 0.02,
            'saldo_devedor_calculado' => 0,
        ]);

        $sd = $this->service->calcularSaldoDevedor($divida, '2026-02-01');

        // 1020 * 1.02 - 0 = 1040.40
        $this->assertEqualsWithDelta(1040.40, $sd, 0.001);
    }

    public function test_desconta_parcelas_pagas_no_mes_de_referencia(): void
    {
        $divida = $this->criarDivida(1000.00);

        HistoricoJuros::create([
            'divida_id' => $divida->id,
            'mes_referencia' => '2026-01-01',
            'taxa_juros' => 0.02,
            'saldo_devedor_calculado' => 0,
        ]);
        $divida->parcelas()->create([
            'numero_parcela' => 1,
            'valor' => 200.00,
            'data_vencimento' => '2026-01-15',
            'status_pagamento' => 'paga',
        ]);

        $sd = $this->service->calcularSaldoDevedor($divida, '2026-01-01');

        // 1000 * 1.02 - 200 = 820.00
        $this->assertEqualsWithDelta(820.00, $sd, 0.001);
    }

    public function test_soma_multiplas_parcelas_pagas_no_mesmo_mes(): void
    {
        $divida = $this->criarDivida(1000.00);

        HistoricoJuros::create([
            'divida_id' => $divida->id,
            'mes_referencia' => '2026-01-01',
            'taxa_juros' => 0,
            'saldo_devedor_calculado' => 0,
        ]);
        $divida->parcelas()->createMany([
            ['numero_parcela' => 1, 'valor' => 100.00, 'data_vencimento' => '2026-01-05', 'status_pagamento' => 'paga'],
            ['numero_parcela' => 2, 'valor' => 50.00, 'data_vencimento' => '2026-01-20', 'status_pagamento' => 'paga'],
        ]);

        $sd = $this->service->calcularSaldoDevedor($divida, '2026-01-01');

        // 1000 * 1 - (100 + 50) = 850.00
        $this->assertEqualsWithDelta(850.00, $sd, 0.001);
    }

    public function test_ignora_parcelas_pendentes(): void
    {
        $divida = $this->criarDivida(1000.00);

        HistoricoJuros::create([
            'divida_id' => $divida->id,
            'mes_referencia' => '2026-01-01',
            'taxa_juros' => 0,
            'saldo_devedor_calculado' => 0,
        ]);
        $divida->parcelas()->create([
            'numero_parcela' => 1,
            'valor' => 200.00,
            'data_vencimento' => '2026-01-15',
            'status_pagamento' => 'pendente',
        ]);

        $sd = $this->service->calcularSaldoDevedor($divida, '2026-01-01');

        // parcela pendente nao entra no calculo: 1000 * 1 - 0 = 1000.00
        $this->assertEqualsWithDelta(1000.00, $sd, 0.001);
    }

    public function test_ignora_parcelas_pagas_em_outro_mes(): void
    {
        $divida = $this->criarDivida(1000.00);

        HistoricoJuros::create([
            'divida_id' => $divida->id,
            'mes_referencia' => '2026-02-01',
            'taxa_juros' => 0,
            'saldo_devedor_calculado' => 0,
        ]);
        $divida->parcelas()->create([
            'numero_parcela' => 1,
            'valor' => 200.00,
            'data_vencimento' => '2026-01-15',
            'status_pagamento' => 'paga',
        ]);

        $sd = $this->service->calcularSaldoDevedor($divida, '2026-02-01');

        // parcela paga em janeiro nao conta no calculo de fevereiro: 1000 * 1 - 0 = 1000.00
        $this->assertEqualsWithDelta(1000.00, $sd, 0.001);
    }

    public function test_ignora_parcelas_de_outra_divida(): void
    {
        $divida = $this->criarDivida(1000.00);
        $outraDivida = $this->criarDivida(500.00);

        HistoricoJuros::create([
            'divida_id' => $divida->id,
            'mes_referencia' => '2026-01-01',
            'taxa_juros' => 0,
            'saldo_devedor_calculado' => 0,
        ]);
        $outraDivida->parcelas()->create([
            'numero_parcela' => 1,
            'valor' => 999.00,
            'data_vencimento' => '2026-01-15',
            'status_pagamento' => 'paga',
        ]);

        $sd = $this->service->calcularSaldoDevedor($divida, '2026-01-01');

        // parcela de outra divida nao pode afetar este calculo
        $this->assertEqualsWithDelta(1000.00, $sd, 0.001);
    }

    public function test_assume_taxa_zero_quando_nao_ha_historico_para_o_mes_corrente(): void
    {
        $divida = $this->criarDivida(1000.00);

        // Nenhum HistoricoJuros criado para o mes - simula RF006 disparando antes do RF005
        $sd = $this->service->calcularSaldoDevedor($divida, '2026-01-01');

        $this->assertEqualsWithDelta(1000.00, $sd, 0.001);

        $this->assertDatabaseHas('historico_juros', [
            'divida_id' => $divida->id,
            'mes_referencia' => '2026-01-01',
            'taxa_juros' => '0.0000',
        ]);
    }

    public function test_persiste_resultado_em_historico_juros(): void
    {
        $divida = $this->criarDivida(1000.00);

        HistoricoJuros::create([
            'divida_id' => $divida->id,
            'mes_referencia' => '2026-01-01',
            'taxa_juros' => 0.02,
            'saldo_devedor_calculado' => 0,
        ]);

        $this->service->calcularSaldoDevedor($divida, '2026-01-01');

        $this->assertDatabaseHas('historico_juros', [
            'divida_id' => $divida->id,
            'mes_referencia' => '2026-01-01',
            'saldo_devedor_calculado' => '1020.00',
        ]);
    }

    public function test_atualiza_cache_de_saldo_devedor_atual_na_divida(): void
    {
        $divida = $this->criarDivida(1000.00);

        HistoricoJuros::create([
            'divida_id' => $divida->id,
            'mes_referencia' => '2026-01-01',
            'taxa_juros' => 0.02,
            'saldo_devedor_calculado' => 0,
        ]);

        $this->service->calcularSaldoDevedor($divida, '2026-01-01');

        $this->assertEqualsWithDelta(1020.00, (float) $divida->fresh()->saldo_devedor_atual, 0.001);
    }

    public function test_recalculo_do_mesmo_mes_e_idempotente_e_nao_duplica_historico(): void
    {
        $divida = $this->criarDivida(1000.00);

        HistoricoJuros::create([
            'divida_id' => $divida->id,
            'mes_referencia' => '2026-01-01',
            'taxa_juros' => 0.02,
            'saldo_devedor_calculado' => 0,
        ]);

        $this->service->calcularSaldoDevedor($divida, '2026-01-01');
        $this->service->calcularSaldoDevedor($divida, '2026-01-01');

        $this->assertSame(
            1,
            HistoricoJuros::where('divida_id', $divida->id)
                ->where('mes_referencia', '2026-01-01')
                ->count()
        );
    }
}
