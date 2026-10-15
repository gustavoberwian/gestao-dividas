<?php

namespace Tests\Feature;

use App\Models\Divida;
use App\Models\Fatura;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * CHECK parcelas_fatura_ou_divida_check (RNF003 / integridade): uma parcela pertence a
 * exatamente uma fatura OU a uma divida. Insercao direta no banco (sem Eloquent), para
 * provar que a regra esta no PostgreSQL e nao so na aplicacao.
 */
class IntegridadeBancoTest extends TestCase
{
    use RefreshDatabase;

    private function inserirParcela(?int $faturaId, ?int $dividaId): void
    {
        DB::table('parcelas')->insert([
            'fatura_id' => $faturaId,
            'divida_id' => $dividaId,
            'numero_parcela' => 1,
            'valor' => 100,
            'data_vencimento' => '2026-10-10',
            'status_pagamento' => 'pendente',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** Executa em savepoint: no PostgreSQL um erro aborta a transacao do teste inteira. */
    private function assertBancoRecusa(?int $faturaId, ?int $dividaId): void
    {
        try {
            DB::transaction(fn () => $this->inserirParcela($faturaId, $dividaId));
            $this->fail('O banco deveria recusar a parcela.');
        } catch (QueryException $e) {
            $this->assertStringContainsString('parcelas_fatura_ou_divida_check', $e->getMessage());
        }
    }

    public function test_parcela_com_fatura_e_divida_e_recusada_pelo_banco(): void
    {
        $this->assertBancoRecusa(Fatura::factory()->create()->id, Divida::factory()->create()->id);
        $this->assertSame(0, DB::table('parcelas')->count());
    }

    public function test_parcela_sem_fatura_e_sem_divida_e_recusada_pelo_banco(): void
    {
        $this->assertBancoRecusa(null, null);
        $this->assertSame(0, DB::table('parcelas')->count());
    }

    public function test_parcela_com_exatamente_um_vinculo_e_aceita(): void
    {
        $this->inserirParcela(null, Divida::factory()->create()->id);
        $this->inserirParcela(Fatura::factory()->create()->id, null);

        $this->assertSame(2, DB::table('parcelas')->count());
    }

    public function test_constraint_existe_no_catalogo_do_postgresql(): void
    {
        $definicao = DB::selectOne(
            "SELECT pg_get_constraintdef(oid) AS def FROM pg_constraint WHERE conname = 'parcelas_fatura_ou_divida_check'"
        );

        $this->assertNotNull($definicao, 'constraint não encontrada');
        $this->assertStringContainsString('CHECK', $definicao->def);
    }
}
