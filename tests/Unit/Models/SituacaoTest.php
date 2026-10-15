<?php

namespace Tests\Unit\Models;

use App\Models\CartaoCredito;
use App\Models\Fatura;
use App\Models\Parcela;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Bloco F / K: situacao derivada (calculada na leitura) de parcelas e faturas,
 * com datas fixas no fuso America/Sao_Paulo.
 */
class SituacaoTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function hoje(string $dataHora): void
    {
        Carbon::setTestNow(Carbon::parse($dataHora, 'America/Sao_Paulo'));
    }

    private function parcela(string $vencimento, string $status = 'pendente'): Parcela
    {
        return new Parcela(['data_vencimento' => $vencimento, 'status_pagamento' => $status, 'valor' => 100]);
    }

    private function fatura(int $diaFechamento, int $diaVencimento, string $mesReferencia, string $status = 'aberta'): Fatura
    {
        $fatura = new Fatura(['mes_referencia' => $mesReferencia, 'valor_total' => 100, 'status_pagamento' => $status]);
        $fatura->setRelation('cartaoCredito', new CartaoCredito([
            'dia_fechamento' => $diaFechamento,
            'dia_vencimento' => $diaVencimento,
        ]));

        return $fatura;
    }

    // ---- Parcela -----------------------------------------------------------

    public static function casosParcela(): array
    {
        return [
            'venceu ontem' => ['2026-10-14', 'pendente', 'vencida'],
            'vence hoje (limite)' => ['2026-10-15', 'pendente', 'vence_hoje'],
            'vence amanha' => ['2026-10-16', 'pendente', 'pendente'],
            'paga e atrasada' => ['2026-09-01', 'paga', 'paga'],
        ];
    }

    #[DataProvider('casosParcela')]
    public function test_situacao_da_parcela(string $vencimento, string $status, string $esperado): void
    {
        $this->hoje('2026-10-15 10:00');

        $this->assertSame($esperado, $this->parcela($vencimento, $status)->situacao());
    }

    public function test_parcela_usa_o_fuso_de_sao_paulo(): void
    {
        // 23h30 em Sao Paulo ja e o dia 16 em UTC; a parcela do dia 15 ainda "vence hoje"
        $this->hoje('2026-10-15 23:30');

        $this->assertSame('vence_hoje', $this->parcela('2026-10-15')->situacao());
    }

    // ---- Fatura ------------------------------------------------------------

    public static function casosFatura(): array
    {
        // cartao: fecha dia 3, vence dia 10; fatura de 10/2026 -> fecha 03/10, vence 10/10
        return [
            'antes do fechamento' => ['2026-10-02', 'aberta'],
            'no dia do fechamento' => ['2026-10-03', 'aberta'],
            'depois do fechamento' => ['2026-10-04', 'fechada'],
            'no dia do vencimento (limite)' => ['2026-10-10', 'fechada'],
            'depois do vencimento' => ['2026-10-11', 'vencida'],
        ];
    }

    #[DataProvider('casosFatura')]
    public function test_situacao_da_fatura(string $hoje, string $esperado): void
    {
        $this->hoje("{$hoje} 12:00");
        $fatura = $this->fatura(3, 10, '2026-10-01');

        $this->assertSame('2026-10-03', $fatura->dataFechamento()->toDateString());
        $this->assertSame('2026-10-10', $fatura->dataVencimento()->toDateString());
        $this->assertSame($esperado, $fatura->situacao());
    }

    public function test_fatura_paga_e_sempre_paga(): void
    {
        $this->hoje('2026-12-31 12:00');

        $this->assertSame('paga', $this->fatura(3, 10, '2026-10-01', 'paga')->situacao());
    }

    public function test_fechamento_maior_que_vencimento_fecha_no_mes_anterior(): void
    {
        // fecha dia 25, vence dia 5: fatura de 10/2026 fecha em 25/09 e vence em 05/10
        $fatura = $this->fatura(25, 5, '2026-10-01');
        $this->assertSame('2026-09-25', $fatura->dataFechamento()->toDateString());
        $this->assertSame('2026-10-05', $fatura->dataVencimento()->toDateString());

        $this->hoje('2026-09-25 12:00');
        $this->assertSame('aberta', $fatura->situacao());
        $this->hoje('2026-09-26 12:00');
        $this->assertSame('fechada', $fatura->situacao());
        $this->hoje('2026-10-06 12:00');
        $this->assertSame('vencida', $fatura->situacao());
    }

    public function test_dia_31_em_mes_de_30_dias_vira_ultimo_dia(): void
    {
        // fecha dia 31, vence dia 10: fatura de 10/2026 fecha em 30/09 (setembro tem 30 dias)
        $fatura = $this->fatura(31, 10, '2026-10-01');
        $this->assertSame('2026-09-30', $fatura->dataFechamento()->toDateString());

        $this->hoje('2026-09-30 12:00');
        $this->assertSame('aberta', $fatura->situacao());
        $this->hoje('2026-10-01 12:00');
        $this->assertSame('fechada', $fatura->situacao());
    }

    public function test_vencimento_dia_31_em_fevereiro(): void
    {
        $fatura = $this->fatura(20, 31, '2026-02-01');

        $this->assertSame('2026-02-28', $fatura->dataVencimento()->toDateString());
        $this->assertSame('2026-02-20', $fatura->dataFechamento()->toDateString());
    }
}
