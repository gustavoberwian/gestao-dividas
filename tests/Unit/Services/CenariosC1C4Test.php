<?php

namespace Tests\Unit\Services;

use App\Models\Divida;
use App\Models\HistoricoJuros;
use App\Models\Parcela;
use App\Services\AmortizacaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Cenarios C1 a C4 do Capitulo 5 (RF005, RF006, RF007; RNF003).
 *
 * Formula: SD = SD_anterior x (1 + i) - P, arredondando a 2 casas a cada mes.
 * Valores esperados calculados a mao, independentes do codigo (SPEC_TESTES.md).
 * AmortizacaoService::calcularSaldoDevedor e chamado mes a mes, em ordem.
 */
class CenariosC1C4Test extends TestCase
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

    /**
     * [valor principal, [[mes, taxa (fracao), pagamento], ...], [saldo esperado por mes]]
     */
    public static function cenarios(): array
    {
        return [
            // C1: 10.000,00; pagamentos de 1.000,00 nos meses 1 a 3; taxas 1,5%, 1,2% e 1,0%
            'C1' => [10000.00, [['2026-01', 0.015, 1000.00], ['2026-02', 0.012, 1000.00], ['2026-03', 0.010, 1000.00]], [9150.00, 8259.80, 7342.40]],
            // C2: 5.000,00; taxa 2,0% nos meses 1 e 2; nenhum pagamento
            'C2' => [5000.00, [['2026-01', 0.020, 0.0], ['2026-02', 0.020, 0.0]], [5100.00, 5202.00]],
            // C3: 1.000,00; taxa 1,5% no mes 1; pagamento de 1.015,00 (quitacao)
            'C3' => [1000.00, [['2026-01', 0.015, 1015.00]], [0.00]],
            // C4: 2.000,00; taxa 0,0% no mes 1; pagamento de 500,00
            'C4' => [2000.00, [['2026-01', 0.000, 500.00]], [1500.00]],
        ];
    }

    #[DataProvider('cenarios')]
    public function test_cenario(float $principal, array $meses, array $esperados): void
    {
        $divida = Divida::factory()->valor($principal)->create();
        $servico = app(AmortizacaoService::class);

        foreach ($meses as $i => [$mes, $taxa, $pagamento]) {
            HistoricoJuros::factory()->for($divida)->create([
                'mes_referencia' => "{$mes}-01", 'taxa_juros' => $taxa,
            ]);
            if ($pagamento > 0) {
                Parcela::factory()->for($divida)->paga()->create([
                    'numero_parcela' => $i + 1, 'valor' => $pagamento, 'data_vencimento' => "{$mes}-10",
                ]);
            }

            $obtido = $servico->calcularSaldoDevedor($divida, "{$mes}-01");

            $this->assertEqualsWithDelta($esperados[$i], $obtido, 0.01, "retorno do mês {$mes}");

            // persistido em HistoricoJuros e no cache da divida com o mesmo valor
            $historico = HistoricoJuros::where('divida_id', $divida->id)->whereDate('mes_referencia', "{$mes}-01")->sole();
            $this->assertSame(number_format($esperados[$i], 2, '.', ''), $historico->saldo_devedor_calculado, "histórico do mês {$mes}");
            $this->assertSame(number_format($esperados[$i], 2, '.', ''), $divida->fresh()->saldo_devedor_atual, "cache após o mês {$mes}");
        }
    }

    public function test_c1_parcela_pendente_nao_entra_no_pagamento_do_mes(): void
    {
        $divida = Divida::factory()->valor(10000)->create();
        HistoricoJuros::factory()->for($divida)->create(['mes_referencia' => '2026-01-01', 'taxa_juros' => 0.015]);
        Parcela::factory()->for($divida)->create(['valor' => 1000, 'data_vencimento' => '2026-01-10', 'status_pagamento' => 'pendente']);

        // so juros: 10.000,00 x 1,015 = 10.150,00
        $this->assertEqualsWithDelta(10150.00, app(AmortizacaoService::class)->calcularSaldoDevedor($divida, '2026-01-01'), 0.01);
    }
}
