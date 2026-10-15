<?php

namespace Tests\Feature;

use App\Models\CartaoCredito;
use App\Models\Divida;
use App\Models\Fatura;
use App\Models\HistoricoJuros;
use App\Models\Parcela;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Cenario C6 (RNF002): o usuario B nao acessa dados do usuario A.
 * Autenticado como B, cada rota de A responde 403 ou 404 e as listagens de B nao
 * contem registros de A. Como visitante, as mesmas rotas redirecionam para o login.
 */
class IsolamentoEntreUsuariosTest extends TestCase
{
    use RefreshDatabase;

    private User $a;

    private User $b;

    private array $dadosA;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-15 10:00', 'America/Sao_Paulo'));

        $this->a = User::factory()->create(['renda_mensal' => 9000]);
        $this->b = User::factory()->create(['renda_mensal' => 2000]);

        $divida = Divida::factory()->for($this->a)->valor(77777)->create(['descricao' => 'DIVIDA-SECRETA-DE-A', 'credor' => 'CREDOR-DE-A']);
        HistoricoJuros::factory()->for($divida)->create(['mes_referencia' => '2026-09-01', 'saldo_devedor_calculado' => 77777]);
        $parcela = Parcela::factory()->for($divida)->create(['valor' => 333, 'data_vencimento' => '2026-10-20']);
        $cartao = CartaoCredito::factory()->for($this->a)->create(['nome_cartao' => 'CARTAO-SECRETO-DE-A']);
        $fatura = Fatura::factory()->for($cartao)->create(['mes_referencia' => '2026-10-01', 'valor_total' => 4444]);

        $this->dadosA = compact('divida', 'parcela', 'cartao', 'fatura');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** [metodo, url (com placeholders), corpo] */
    public static function rotasDeA(): array
    {
        return [
            'GET /dividas/{divida}' => ['get', '/dividas/{divida}', []],
            'GET /dividas/{divida}/relatorio' => ['get', '/dividas/{divida}/relatorio', []],
            'POST /dividas/{divida}/juros' => ['post', '/dividas/{divida}/juros', ['mes_referencia' => '2026-10-01', 'taxa_juros' => '1,5']],
            'POST /dividas/{divida}/parcelas' => ['post', '/dividas/{divida}/parcelas', ['numero_parcela' => 9, 'valor' => '10,00', 'data_vencimento' => '2026-10-20']],
            'PATCH /parcelas/{parcela}/pagar' => ['patch', '/parcelas/{parcela}/pagar', []],
            'PATCH /faturas/{fatura}/pagar' => ['patch', '/faturas/{fatura}/pagar', []],
            'GET /cartoes/{cartao}' => ['get', '/cartoes/{cartao}', []],
            'POST /faturas (cartão de A)' => ['post', '/faturas', ['cartao_credito_id' => '{cartao}', 'mes_referencia' => '2026-11-01', 'valor_total' => '1,00']],
        ];
    }

    private function preencher(string $texto): string
    {
        foreach ($this->dadosA as $chave => $modelo) {
            $texto = str_replace('{'.$chave.'}', (string) $modelo->id, $texto);
        }

        return $texto;
    }

    #[DataProvider('rotasDeA')]
    public function test_c6_usuario_b_e_negado_nas_rotas_de_a(string $metodo, string $url, array $corpo): void
    {
        $corpo = array_map(fn ($valor) => is_string($valor) ? $this->preencher($valor) : $valor, $corpo);
        $antes = $this->retrato();

        $response = $this->actingAs($this->b)->{$metodo}($this->preencher($url), $corpo);

        // 403/404 conforme a SPEC_TESTES; 405 so para GET /dividas/{divida}, rota que nao
        // existe no sistema (a tela da divida e /dividas/{divida}/relatorio)
        $permitidos = $url === '/dividas/{divida}' ? [403, 404, 405] : [403, 404];
        $this->assertContains($response->status(), $permitidos, "status {$response->status()} em {$metodo} {$url}");
        $this->assertStringNotContainsString('DIVIDA-SECRETA-DE-A', $response->getContent());
        $this->assertStringNotContainsString('CARTAO-SECRETO-DE-A', $response->getContent());
        $this->assertEquals($antes, $this->retrato(), 'nenhum dado de A pode mudar');
    }

    #[DataProvider('rotasDeA')]
    public function test_c6_visitante_e_redirecionado_ao_login(string $metodo, string $url, array $corpo): void
    {
        if ($url === '/dividas/{divida}') {
            // GET nessa URI nao existe (405) antes de chegar na autenticacao
            $this->assertSame(405, $this->get($this->preencher($url))->status());

            return;
        }

        $this->{$metodo}($this->preencher($url), $corpo)->assertRedirect(route('login'));
    }

    public function test_c6_listagens_de_b_nao_contem_registros_de_a(): void
    {
        $this->actingAs($this->b);

        foreach (['/dividas', '/cartoes', '/dashboard'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertStringNotContainsString('DIVIDA-SECRETA-DE-A', $html, $url);
            $this->assertStringNotContainsString('CARTAO-SECRETO-DE-A', $html, $url);
            $this->assertStringNotContainsString('77.777,00', $html, $url);
        }
    }

    public function test_c6_dashboard_de_b_nao_inclui_valores_de_a(): void
    {
        $response = $this->actingAs($this->b)->get('/dashboard');

        // B nao tem faturas nem parcelas: RLR = renda de B
        $this->assertEqualsWithDelta(0.0, $response->viewData('totalFaturasAbertas'), 0.001);
        $this->assertEqualsWithDelta(0.0, $response->viewData('totalParcelasDividasDoMes'), 0.001);
        $this->assertEqualsWithDelta(2000.0, $response->viewData('saldoDisponivel'), 0.001);
        $this->assertTrue($response->viewData('seriesSaldoDevedor')->isEmpty());
        $this->assertTrue($response->viewData('proximosVencimentos')->isEmpty());
    }

    /** Estado dos registros de A, para garantir que nenhuma tentativa de B os alterou. */
    private function retrato(): array
    {
        return [
            'divida' => $this->dadosA['divida']->fresh()->getAttributes(),
            'parcelas' => Parcela::where('divida_id', $this->dadosA['divida']->id)->orderBy('id')->get()->toArray(),
            'historico' => HistoricoJuros::where('divida_id', $this->dadosA['divida']->id)->orderBy('id')->get()->toArray(),
            'faturas' => Fatura::where('cartao_credito_id', $this->dadosA['cartao']->id)->orderBy('id')->get()->toArray(),
        ];
    }
}
