<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Fluxo completo do C1 somente pelas rotas HTTP, com os valores digitados como na tela
 * (mascaras), seguindo o criterio de pronto e as tarefas T1 a T6 do roteiro:
 * T1 renda -> T2 divida -> T3 parcela, marcar paga, taxa "1,5" -> T4 saldo e relatorio
 * -> T5 cartao e fatura -> T6 Renda Livre Real no dashboard.
 * (RF002, RF003, RF004, RF005, RF006, RF007, RF008, RF009, RF011)
 */
class FluxoCompletoC1Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-04-15 10:00', 'America/Sao_Paulo'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_fluxo_completo_do_c1_pela_interface(): void
    {
        $user = User::factory()->create(['renda_mensal' => null]);
        $this->actingAs($user);

        // T1: renda mensal (RF002)
        $this->from('/dashboard')->put('/perfil/renda', ['renda_mensal' => 'R$ 5.000,00'])
            ->assertRedirect('/dashboard')->assertSessionHasNoErrors();

        // T2: divida (RF004)
        $this->from('/dividas')->post('/dividas', [
            '_form' => 'modalNovaDivida', 'descricao' => 'Empréstimo C1', 'credor' => 'Banco X',
            'valor_principal' => 'R$ 10.000,00', 'data_inicio' => '2026-01-01',
        ])->assertRedirect('/dividas')->assertSessionHasNoErrors();
        $divida = $user->dividas()->sole();
        $relatorio = route('dividas.relatorio', $divida);

        // T3: em cada mes, cadastrar a parcela, marca-la como paga e lancar a taxa (RF005-RF007)
        $meses = [['2026-01', '1,5', '9.150,00'], ['2026-02', '1,2', '8.259,80'], ['2026-03', '1,0', '7.342,40']];
        foreach ($meses as $i => [$mes, $taxa, $saldoEsperado]) {
            $this->from($relatorio)->post(route('dividas.parcelas.store', $divida), [
                '_form' => 'modalNovaParcela', 'numero_parcela' => $i + 1, 'valor' => 'R$ 1.000,00', 'data_vencimento' => "{$mes}-10",
            ])->assertRedirect($relatorio)->assertSessionHasNoErrors();

            $parcela = $divida->parcelas()->where('numero_parcela', $i + 1)->sole();
            $this->from($relatorio)->patch(route('parcelas.pagar', $parcela))->assertRedirect($relatorio);

            $this->from($relatorio)->post(route('dividas.juros.store', $divida), [
                '_form' => 'modalNovaTaxa', 'mes_referencia' => "{$mes}-01", 'taxa_juros' => $taxa,
            ])->assertRedirect($relatorio)->assertSessionHasNoErrors();

            // T4: o saldo aparece no relatorio (RF009)
            $this->get($relatorio)->assertOk()->assertSee('R$ '.$saldoEsperado);
        }

        $this->assertSame('7342.40', $divida->fresh()->saldo_devedor_atual);
        $this->get($relatorio)
            ->assertSeeInOrder(['01/2026', '1,50%', 'R$ 9.150,00', '02/2026', '1,20%', 'R$ 8.259,80', '03/2026', '1,00%', 'R$ 7.342,40'])
            ->assertSee('Quanto do que você pagou foi só juros');
        $this->get('/dividas')->assertSee('Empréstimo C1')->assertSee('R$ 7.342,40');

        // T5: cartao e fatura (RF003, RF011)
        $this->from('/cartoes')->post('/cartoes', [
            '_form' => 'modalNovoCartao', 'nome_cartao' => 'Nubank', 'limite' => 'R$ 3.000,00', 'dia_fechamento' => '3', 'dia_vencimento' => '10',
        ])->assertSessionHasNoErrors();
        $cartao = $user->cartoesCredito()->sole();
        $this->from(route('cartoes.show', $cartao))->post('/faturas', [
            '_form' => 'modalNovaFatura', 'cartao_credito_id' => $cartao->id, 'mes_referencia' => '2026-05-01', 'valor_total' => 'R$ 1.200,00',
        ])->assertSessionHasNoErrors();
        $this->get(route('cartoes.show', $cartao))->assertSee('05/2026')->assertSee('R$ 1.200,00');

        // T6: Renda Livre Real = 5.000,00 - 1.200,00 (fatura aberta) - 0 (parcelas do mes ja pagas)
        $dashboard = $this->get('/dashboard')->assertOk();
        $this->assertEqualsWithDelta(3800.00, $dashboard->viewData('saldoDisponivel'), 0.01);
        $dashboard->assertSee('Renda Livre Real')->assertSee('R$ 3.800,00');
    }
}
