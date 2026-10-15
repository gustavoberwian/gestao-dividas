<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvisosTest extends TestCase
{
    use RefreshDatabase;

    // Bloco D: a mensagem de status vira toast (dados para ui.js), sem o antigo cartao de alerta
    public function test_mensagem_de_status_e_entregue_ao_toast(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from('/dashboard')->put('/perfil/renda', ['renda_mensal' => '5.000,00']);
        $response = $this->get('/dashboard');

        $response->assertSee('window.avisosDaPagina', false);
        $response->assertSee('"tipo":"success","mensagem":"Renda mensal atualizada com sucesso."', false);
        $response->assertDontSee('alert alert-success', false);
    }

    // Bloco D: erro de validacao aparece inline, abaixo do campo, apenas no formulario enviado
    public function test_erro_de_validacao_fica_inline_no_formulario_enviado(): void
    {
        $user = User::factory()->create();
        $divida = $user->dividas()->create([
            'descricao' => 'Empréstimo', 'valor_principal' => 1000, 'credor' => 'Banco X',
            'data_inicio' => '2026-01-01', 'saldo_devedor_atual' => 1000,
        ]);
        $relatorio = route('dividas.relatorio', $divida);

        $this->actingAs($user)->from($relatorio)->post(route('dividas.juros.store', $divida), [
            '_form' => 'modalNovaTaxa',
            'mes_referencia' => '2026-01-01',
            'taxa_juros' => '150',
        ])->assertRedirect($relatorio);

        $html = $this->get($relatorio)->getContent();

        $this->assertMatchesRegularExpression(
            '/id="modalNovaTaxa_taxa_juros"[^>]*class="[^"]*is-invalid/s',
            $html
        );
        $this->assertStringContainsString('<div class="invalid-feedback">A taxa de juros deve estar entre 0% e 100%.</div>', $html);
        // o outro modal da mesma pagina nao recebe destaque de erro
        $this->assertDoesNotMatchRegularExpression('/id="modalNovaParcela_[a-z_]+"[^>]*is-invalid/s', $html);
        $this->assertStringContainsString('"formulario":"modalNovaTaxa"', $html);
    }
}
