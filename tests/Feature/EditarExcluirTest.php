<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Bloco G / K (RNF002): cada rota nova de editar/excluir/desfazer.
 * - dono: 302 e a alteracao acontece;
 * - outro usuario: 403 e nada muda;
 * - visitante: redireciona para o login;
 * - filho de outro pai na URL aninhada (scoped binding): 404.
 */
class EditarExcluirTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function dadosDe(User $user): array
    {
        $divida = $user->dividas()->create([
            'descricao' => 'Empréstimo', 'valor_principal' => 1000, 'credor' => 'Banco X',
            'data_inicio' => '2026-01-01', 'saldo_devedor_atual' => 1000,
        ]);
        $historico = $divida->historicoJuros()->create([
            'mes_referencia' => '2026-01-01', 'taxa_juros' => 0.01, 'saldo_devedor_calculado' => 1010,
        ]);
        $parcela = $divida->parcelas()->create([
            'numero_parcela' => 1, 'valor' => 100, 'data_vencimento' => '2026-01-10', 'status_pagamento' => 'paga',
        ]);
        $cartao = $user->cartoesCredito()->create([
            'nome_cartao' => 'Nubank', 'limite' => 1000, 'dia_fechamento' => 3, 'dia_vencimento' => 10,
        ]);
        $fatura = $cartao->faturas()->create([
            'mes_referencia' => '2026-01-01', 'valor_total' => 300, 'status_pagamento' => 'paga',
        ]);

        return compact('divida', 'historico', 'parcela', 'cartao', 'fatura');
    }

    /**
     * [metodo, nome da rota, parametros, corpo, verificacao apos o dono agir]
     */
    public static function rotas(): array
    {
        $divida = ['descricao' => 'Editada', 'credor' => 'Banco Y', 'valor_principal' => '1.000,00', 'data_inicio' => '2026-01-01'];
        $cartao = ['nome_cartao' => 'Editado', 'limite' => '2.000,00', 'dia_fechamento' => 5, 'dia_vencimento' => 12];

        return [
            'editar dívida' => ['put', 'dividas.update', ['divida'], $divida, ['dividas', 'descricao', 'Editada']],
            'excluir dívida' => ['delete', 'dividas.destroy', ['divida'], [], ['dividas', null, null]],
            'editar taxa' => ['put', 'dividas.juros.update', ['divida', 'historico'], ['taxa_juros' => '2'], ['historico_juros', 'taxa_juros', '0.0200']],
            'excluir taxa' => ['delete', 'dividas.juros.destroy', ['divida', 'historico'], [], ['historico_juros', 'taxa_juros', '0.0000']],
            'editar parcela' => ['put', 'dividas.parcelas.update', ['divida', 'parcela'], ['numero_parcela' => 1, 'valor' => '150,00', 'data_vencimento' => '2026-01-10'], ['parcelas', 'valor', '150.00']],
            'excluir parcela' => ['delete', 'dividas.parcelas.destroy', ['divida', 'parcela'], [], ['parcelas', null, null]],
            'desfazer pagamento da parcela' => ['patch', 'dividas.parcelas.reabrir', ['divida', 'parcela'], [], ['parcelas', 'status_pagamento', 'pendente']],
            'editar cartão' => ['put', 'cartoes.update', ['cartao'], $cartao, ['cartoes_credito', 'nome_cartao', 'Editado']],
            'excluir cartão' => ['delete', 'cartoes.destroy', ['cartao'], [], ['cartoes_credito', null, null]],
            'editar fatura' => ['put', 'cartoes.faturas.update', ['cartao', 'fatura'], ['mes_referencia' => '2026-02-01', 'valor_total' => '450,00'], ['faturas', 'valor_total', '450.00']],
            'excluir fatura' => ['delete', 'cartoes.faturas.destroy', ['cartao', 'fatura'], [], ['faturas', null, null]],
            'desfazer pagamento da fatura' => ['patch', 'cartoes.faturas.reabrir', ['cartao', 'fatura'], [], ['faturas', 'status_pagamento', 'aberta']],
        ];
    }

    private function url(string $rota, array $chaves, array $dados): string
    {
        return route($rota, array_map(fn ($chave) => $dados[$chave], $chaves));
    }

    /** O registro "alvo" da rota e o ultimo parametro (ex.: a parcela em /dividas/{d}/parcelas/{p}). */
    private function alvo(array $chaves, array $dados)
    {
        return $dados[end($chaves)];
    }

    #[DataProvider('rotas')]
    public function test_dono_consegue(string $metodo, string $rota, array $chaves, array $corpo, array $verificacao): void
    {
        $dono = User::factory()->create();
        $dados = $this->dadosDe($dono);

        $this->actingAs($dono)->{$metodo}($this->url($rota, $chaves, $dados), $corpo)
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        [$tabela, $coluna, $valor] = $verificacao;
        $id = $this->alvo($chaves, $dados)->id;
        if ($coluna === null) {
            $this->assertDatabaseMissing($tabela, ['id' => $id]);
        } else {
            $this->assertDatabaseHas($tabela, ['id' => $id, $coluna => $valor]);
        }
    }

    #[DataProvider('rotas')]
    public function test_outro_usuario_recebe_403_e_nada_muda(string $metodo, string $rota, array $chaves, array $corpo): void
    {
        $dados = $this->dadosDe(User::factory()->create());
        $alvo = $this->alvo($chaves, $dados);
        $antes = $alvo->fresh()->getAttributes();

        $this->actingAs(User::factory()->create())->{$metodo}($this->url($rota, $chaves, $dados), $corpo)
            ->assertForbidden();

        $this->assertEquals($antes, $alvo->fresh()->getAttributes());
    }

    #[DataProvider('rotas')]
    public function test_visitante_vai_para_o_login(string $metodo, string $rota, array $chaves, array $corpo): void
    {
        $dados = $this->dadosDe(User::factory()->create());

        $this->{$metodo}($this->url($rota, $chaves, $dados), $corpo)->assertRedirect(route('login'));

        $this->assertNotNull($this->alvo($chaves, $dados)->fresh());
    }

    public static function rotasAninhadas(): array
    {
        return [
            'editar taxa' => ['put', 'dividas.juros.update', 'divida', 'historico', ['taxa_juros' => '2']],
            'excluir taxa' => ['delete', 'dividas.juros.destroy', 'divida', 'historico', []],
            'editar parcela' => ['put', 'dividas.parcelas.update', 'divida', 'parcela', ['numero_parcela' => 1, 'valor' => '1,00', 'data_vencimento' => '2026-01-10']],
            'excluir parcela' => ['delete', 'dividas.parcelas.destroy', 'divida', 'parcela', []],
            'desfazer parcela' => ['patch', 'dividas.parcelas.reabrir', 'divida', 'parcela', []],
            'editar fatura' => ['put', 'cartoes.faturas.update', 'cartao', 'fatura', ['mes_referencia' => '2026-02-01', 'valor_total' => '1,00']],
            'excluir fatura' => ['delete', 'cartoes.faturas.destroy', 'cartao', 'fatura', []],
            'desfazer fatura' => ['patch', 'cartoes.faturas.reabrir', 'cartao', 'fatura', []],
        ];
    }

    // Ajuste 2: filho de outro pai -> 404, mesmo quando os dois pais sao do proprio usuario
    #[DataProvider('rotasAninhadas')]
    public function test_filho_de_outro_pai_na_url_aninhada_retorna_404(string $metodo, string $rota, string $pai, string $filho, array $corpo): void
    {
        $user = User::factory()->create();
        $dadosA = $this->dadosDe($user);
        $dadosB = $this->dadosDe($user);
        $antes = $dadosB[$filho]->fresh()->getAttributes();

        $this->actingAs($user)->{$metodo}(route($rota, [$dadosA[$pai], $dadosB[$filho]]), $corpo)->assertNotFound();

        $this->assertEquals($antes, $dadosB[$filho]->fresh()->getAttributes());
    }

    // Ajuste 2: pai e filho corretos, mas de outro usuario -> 403 (nao 404)
    #[DataProvider('rotasAninhadas')]
    public function test_recurso_correto_de_outro_usuario_retorna_403(string $metodo, string $rota, string $pai, string $filho, array $corpo): void
    {
        $dadosOutro = $this->dadosDe(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->{$metodo}(route($rota, [$dadosOutro[$pai], $dadosOutro[$filho]]), $corpo)
            ->assertForbidden();
    }

    public function test_excluir_divida_remove_parcelas_e_historico(): void
    {
        $dono = User::factory()->create();
        $dados = $this->dadosDe($dono);

        $this->actingAs($dono)->delete(route('dividas.destroy', $dados['divida']))
            ->assertRedirect(route('dividas.index'));

        $this->assertDatabaseMissing('parcelas', ['id' => $dados['parcela']->id]);
        $this->assertDatabaseMissing('historico_juros', ['id' => $dados['historico']->id]);
        // o cartao e a fatura nao sao afetados
        $this->assertDatabaseHas('faturas', ['id' => $dados['fatura']->id]);
    }

    public function test_excluir_cartao_remove_faturas(): void
    {
        $dono = User::factory()->create();
        $dados = $this->dadosDe($dono);

        $this->actingAs($dono)->delete(route('cartoes.destroy', $dados['cartao']))
            ->assertRedirect(route('cartoes.index'));

        $this->assertDatabaseMissing('faturas', ['id' => $dados['fatura']->id]);
        $this->assertDatabaseHas('dividas', ['id' => $dados['divida']->id]);
    }

    public function test_telas_mostram_botoes_de_editar_excluir_com_confirmacao(): void
    {
        $dono = User::factory()->create();
        $dados = $this->dadosDe($dono);

        $this->actingAs($dono)->get(route('dividas.relatorio', $dados['divida']))
            ->assertOk()
            ->assertSee('id="modalEditarDivida"', false)
            ->assertSee('id="modalEditarTaxa'.$dados['historico']->id.'"', false)
            ->assertSee('id="modalEditarParcela'.$dados['parcela']->id.'"', false)
            ->assertSee('Desfazer pagamento')
            ->assertSee('data-confirmar="Esta ação não pode ser desfeita. A dívida será excluída junto com todas as suas parcelas e o histórico de juros."', false);

        $this->get(route('cartoes.show', $dados['cartao']))
            ->assertOk()
            ->assertSee('id="modalEditarCartao"', false)
            ->assertSee('id="modalEditarFatura'.$dados['fatura']->id.'"', false)
            ->assertSee('O cartão será excluído junto com todas as suas faturas.');
    }
}
