<?php

namespace Tests\Feature;

use App\Models\Divida;
use App\Models\User;
use App\Services\AmortizacaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Bloco G / K: recalculo em cascata ao editar, excluir e desfazer pagamento.
 * Valores de aceitacao calculados a mao a partir do C1 (10.000,00; taxas 1,5%, 1,2% e
 * 1,0%; pagamentos de 1.000,00 nos meses 1 a 3), arredondando a 2 casas a cada mes.
 */
class RecalculoCascataTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Divida $divida;

    /** Monta o C1 so pelas rotas HTTP: cadastrar parcela -> marcar paga -> lancar taxa. */
    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->divida = $this->user->dividas()->create([
            'descricao' => 'C1', 'valor_principal' => 10000, 'credor' => 'Banco X',
            'data_inicio' => '2026-01-01', 'saldo_devedor_atual' => 10000,
        ]);

        foreach ([['2026-01', '1,5'], ['2026-02', '1,2'], ['2026-03', '1,0']] as $i => [$mes, $taxa]) {
            $this->actingAs($this->user)->post(route('dividas.parcelas.store', $this->divida), [
                'numero_parcela' => $i + 1, 'valor' => '1.000,00', 'data_vencimento' => "{$mes}-10",
            ])->assertSessionHasNoErrors();
            $this->patch(route('parcelas.pagar', $this->divida->parcelas()->where('numero_parcela', $i + 1)->sole()));
            $this->post(route('dividas.juros.store', $this->divida), ['mes_referencia' => "{$mes}-01", 'taxa_juros' => $taxa])
                ->assertSessionHasNoErrors();
        }

        $this->assertSaldos([9150.00, 8259.80, 7342.40]);
    }

    private function assertSaldos(array $esperados): void
    {
        $obtidos = $this->divida->historicoJuros()->orderBy('mes_referencia')->pluck('saldo_devedor_calculado')
            ->map(fn ($valor) => (float) $valor)->all();

        $this->assertCount(count($esperados), $obtidos);
        foreach ($esperados as $i => $esperado) {
            $this->assertEqualsWithDelta($esperado, $obtidos[$i], 0.01, 'mês '.($i + 1));
        }
        $this->assertEqualsWithDelta(end($esperados), (float) $this->divida->fresh()->saldo_devedor_atual, 0.01, 'cache da dívida');
    }

    private function parcela(int $numero)
    {
        return $this->divida->parcelas()->where('numero_parcela', $numero)->sole();
    }

    private function taxa(string $mes)
    {
        return $this->divida->historicoJuros()->whereDate('mes_referencia', $mes)->sole();
    }

    // Aceitacao 1: editar o pagamento do mes 1 para 500,00
    public function test_editar_pagamento_do_mes_1_recalcula_os_meses_seguintes(): void
    {
        $this->put(route('dividas.parcelas.update', [$this->divida, $this->parcela(1)]), [
            'numero_parcela' => 1, 'valor' => 'R$ 500,00', 'data_vencimento' => '2026-01-10',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSaldos([9650.00, 8765.80, 7853.46]);
    }

    // Aceitacao 2: excluir a taxa do mes 2 (o mes tem pagamento -> fica com 0,00%)
    public function test_excluir_taxa_de_mes_com_pagamento_vira_zero_e_recalcula(): void
    {
        $this->delete(route('dividas.juros.destroy', [$this->divida, $this->taxa('2026-02-01')]))->assertRedirect();

        $this->assertEqualsWithDelta(0.0, (float) $this->taxa('2026-02-01')->taxa_juros, 0.00001);
        $this->assertSaldos([9150.00, 8150.00, 7231.50]);
    }

    // Aceitacao 3: desfazer o pagamento do mes 3
    public function test_desfazer_pagamento_do_mes_3(): void
    {
        $this->patch(route('dividas.parcelas.reabrir', [$this->divida, $this->parcela(3)]))->assertRedirect();

        $this->assertSame('pendente', $this->parcela(3)->status_pagamento);
        $this->assertSaldos([9150.00, 8259.80, 8342.40]);
    }

    public function test_editar_taxa_do_mes_1_recalcula_os_meses_seguintes(): void
    {
        // mes 1 com 2%: 10200 - 1000 = 9200; mes 2: 9200 x 1,012 = 9310,40 - 1000 = 8310,40;
        // mes 3: 8310,40 x 1,01 = 8393,504 -> 8393,50 - 1000 = 7393,50
        $this->put(route('dividas.juros.update', [$this->divida, $this->taxa('2026-01-01')]), ['taxa_juros' => '2'])
            ->assertSessionHasNoErrors();

        $this->assertSaldos([9200.00, 8310.40, 7393.50]);
    }

    public function test_excluir_parcela_paga_recalcula(): void
    {
        $this->delete(route('dividas.parcelas.destroy', [$this->divida, $this->parcela(2)]))->assertRedirect();

        // mes 2 sem pagamento: 9150 x 1,012 = 9259,80; mes 3: 9259,80 x 1,01 = 9352,398 -> 9352,40 - 1000
        $this->assertSaldos([9150.00, 9259.80, 8352.40]);
    }

    public function test_excluir_taxa_de_mes_sem_pagamento_remove_o_registro(): void
    {
        $this->patch(route('dividas.parcelas.reabrir', [$this->divida, $this->parcela(3)]));
        $this->delete(route('dividas.juros.destroy', [$this->divida, $this->taxa('2026-03-01')]))->assertRedirect();

        $this->assertSame(0, $this->divida->historicoJuros()->whereDate('mes_referencia', '2026-03-01')->count());
        $this->assertSaldos([9150.00, 8259.80]);
    }

    public function test_editar_valor_principal_recalcula_todos_os_meses(): void
    {
        $this->put(route('dividas.update', $this->divida), [
            'descricao' => 'C1', 'credor' => 'Banco X', 'valor_principal' => 'R$ 20.000,00', 'data_inicio' => '2026-01-01',
        ])->assertSessionHasNoErrors();

        // 20300 - 1000 = 19300; 19531,60 - 1000 = 18531,60; 18716,916 -> 18716,92 - 1000 = 17716,92
        $this->assertSaldos([19300.00, 18531.60, 17716.92]);
    }

    // Ajuste 3: registro de 0,00% criado ao pagar parcela permanece apos desfazer o pagamento
    public function test_registro_de_zero_por_cento_criado_ao_pagar_permanece_apos_desfazer(): void
    {
        $this->post(route('dividas.parcelas.store', $this->divida), [
            'numero_parcela' => 4, 'valor' => '500,00', 'data_vencimento' => '2026-04-10',
        ]);
        $this->patch(route('parcelas.pagar', $this->parcela(4)));
        $this->assertEqualsWithDelta(0.0, (float) $this->taxa('2026-04-01')->taxa_juros, 0.00001);
        $this->assertSaldos([9150.00, 8259.80, 7342.40, 6842.40]);

        $this->patch(route('dividas.parcelas.reabrir', [$this->divida, $this->parcela(4)]));

        $this->assertSaldos([9150.00, 8259.80, 7342.40, 7342.40]);
    }

    // Ajuste 4: meses sem taxa lancada continuam sem juros (limitacao registrada no artigo)
    public function test_mes_sem_taxa_nao_gera_juros_e_nao_e_criado_pela_cascata(): void
    {
        // taxa em maio, sem nada em abril: abril nao recebe registro nem juros
        $this->post(route('dividas.juros.store', $this->divida), ['mes_referencia' => '2026-05-01', 'taxa_juros' => '1']);

        $this->assertSame(0, $this->divida->historicoJuros()->whereDate('mes_referencia', '2026-04-01')->count());
        // maio: 7342,40 x 1,01 = 7415,824 -> 7415,82
        $this->assertSaldos([9150.00, 8259.80, 7342.40, 7415.82]);
    }

    // Ajuste 1: a edicao e o recalculo estao na mesma transacao
    public function test_falha_no_recalculo_desfaz_a_edicao(): void
    {
        $this->mock(AmortizacaoService::class, function ($mock) {
            $mock->shouldReceive('recalcularAPartirDe')->andThrow(new RuntimeException('falha simulada no recálculo'));
        });

        $this->put(route('dividas.parcelas.update', [$this->divida, $this->parcela(1)]), [
            'numero_parcela' => 1, 'valor' => '500,00', 'data_vencimento' => '2026-01-10',
        ])->assertServerError();

        $this->assertEqualsWithDelta(1000.0, (float) $this->parcela(1)->valor, 0.001);
        $this->assertSaldos([9150.00, 8259.80, 7342.40]);
    }

    public function test_falha_no_recalculo_desfaz_a_exclusao_da_taxa(): void
    {
        $this->mock(AmortizacaoService::class, function ($mock) {
            $mock->shouldReceive('recalcularAPartirDe')->andThrow(new RuntimeException('falha simulada no recálculo'));
        });

        $this->delete(route('dividas.juros.destroy', [$this->divida, $this->taxa('2026-02-01')]))->assertServerError();

        $this->assertEqualsWithDelta(0.012, (float) $this->taxa('2026-02-01')->taxa_juros, 0.00001);
    }
}
