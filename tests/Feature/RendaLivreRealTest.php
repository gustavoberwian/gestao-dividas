<?php

namespace Tests\Feature;

use App\Models\CartaoCredito;
use App\Models\Divida;
use App\Models\Fatura;
use App\Models\Parcela;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Cenario C5 (RF008): Renda Livre Real = renda - faturas em aberto - parcelas de
 * dividas pendentes com vencimento no mes corrente.
 * Receita 5.000,00; fatura aberta 1.200,00; parcelas pendentes do mes somando 800,00
 * => 3.000,00. Os casos de borda (a) a (d) NAO podem alterar o resultado.
 */
class RendaLivreRealTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Fatura $faturaAberta;

    private Divida $divida;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-15 10:00', 'America/Sao_Paulo'));

        $this->user = User::factory()->create(['renda_mensal' => 5000]);
        $cartao = CartaoCredito::factory()->for($this->user)->create();
        $this->faturaAberta = Fatura::factory()->for($cartao)->create(['mes_referencia' => '2026-10-01', 'valor_total' => 1200]);

        $this->divida = Divida::factory()->for($this->user)->create();
        $outraDivida = Divida::factory()->for($this->user)->create();
        Parcela::factory()->for($this->divida)->create(['valor' => 500, 'data_vencimento' => '2026-10-20']);
        Parcela::factory()->for($outraDivida)->create(['valor' => 300, 'data_vencimento' => '2026-10-31']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function assertRendaLivreReal(float $esperado): void
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertOk();
        $this->assertEqualsWithDelta($esperado, $response->viewData('saldoDisponivel'), 0.01);
        $response->assertSee('Renda Livre Real');
        $response->assertSee('R$ '.number_format($esperado, 2, ',', '.'));
    }

    public function test_c5_renda_livre_real(): void
    {
        $this->assertRendaLivreReal(3000.00);
    }

    public static function casosDeBorda(): array
    {
        return ['a' => ['a'], 'b' => ['b'], 'c' => ['c'], 'd' => ['d']];
    }

    private function criarCasoDeBorda(string $caso): void
    {
        match ($caso) {
            // (a) parcela vinculada a FATURA, pendente no mes: ja esta no valor da fatura
            'a' => Parcela::factory()->daFatura($this->faturaAberta)->create(['valor' => 700, 'data_vencimento' => '2026-10-10']),
            // (b) fatura com status paga
            'b' => Fatura::factory()->paga()->for($this->faturaAberta->cartaoCredito)->create(['mes_referencia' => '2026-09-01', 'valor_total' => 999]),
            // (c) parcela de divida ja paga, no mes corrente
            'c' => Parcela::factory()->paga()->for($this->divida)->create(['valor' => 150, 'data_vencimento' => '2026-10-20']),
            // (d) parcela de divida pendente com vencimento em outro mes
            'd' => Parcela::factory()->for($this->divida)->create(['valor' => 400, 'data_vencimento' => '2026-11-05']),
        };
    }

    #[DataProvider('casosDeBorda')]
    public function test_c5_caso_de_borda_nao_altera_o_resultado(string $caso): void
    {
        $this->criarCasoDeBorda($caso);

        $this->assertRendaLivreReal(3000.00);
    }

    public function test_c5_todos_os_casos_de_borda_juntos(): void
    {
        foreach (['a', 'b', 'c', 'd'] as $caso) {
            $this->criarCasoDeBorda($caso);
        }

        $this->assertRendaLivreReal(3000.00);
    }

    // Bloco K: parcela vencida de mes anterior gera alerta, mas a formula nao muda
    public function test_parcela_vencida_de_mes_anterior_gera_alerta_sem_alterar_a_formula(): void
    {
        Parcela::factory()->for($this->divida)->create(['valor' => 250, 'data_vencimento' => '2026-09-20']);

        $this->assertRendaLivreReal(3000.00);
        $this->get('/dashboard')
            ->assertSee('1 parcela vencida')
            ->assertSee('somando R$ 250,00', false);
    }

    public function test_sem_parcelas_vencidas_nao_ha_alerta(): void
    {
        $this->actingAs($this->user)->get('/dashboard')->assertDontSee('parcela vencida');
    }

    public function test_renda_livre_real_pode_ficar_negativa(): void
    {
        $this->user->update(['renda_mensal' => 1500]);

        $this->assertRendaLivreReal(-500.00);
    }
}
