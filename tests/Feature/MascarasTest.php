<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\NumeroBr;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Bloco E / K: o servidor aceita os formatos que as mascaras enviam e recusa valores
 * invalidos com mensagem em portugues.
 */
class MascarasTest extends TestCase
{
    use RefreshDatabase;

    public static function formatosDeMoeda(): array
    {
        return [
            'mascara completa' => ['R$ 1.234,56'],
            'milhar e virgula' => ['1.234,56'],
            'so virgula' => ['1234,56'],
            'ponto decimal' => ['1234.56'],
        ];
    }

    #[DataProvider('formatosDeMoeda')]
    public function test_normalizador_aceita_formatos_mascarados(string $digitado): void
    {
        $this->assertSame('1234.56', NumeroBr::normalizar($digitado));
    }

    #[DataProvider('formatosDeMoeda')]
    public function test_cadastro_de_divida_aceita_valor_mascarado(string $digitado): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/dividas', [
            'descricao' => 'Empréstimo', 'credor' => 'Banco X',
            'valor_principal' => $digitado, 'data_inicio' => '2026-01-10',
        ])->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(1234.56, (float) $user->dividas()->sole()->valor_principal, 0.001);
    }

    public function test_valores_mascarados_em_cartao_fatura_e_parcela(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/cartoes', [
            'nome_cartao' => 'Nubank', 'limite' => 'R$ 5.000,00', 'dia_fechamento' => '3', 'dia_vencimento' => '10',
        ])->assertSessionHasNoErrors();
        $cartao = $user->cartoesCredito()->sole();
        $this->assertEqualsWithDelta(5000, (float) $cartao->limite, 0.001);

        $this->post('/faturas', [
            'cartao_credito_id' => $cartao->id, 'mes_referencia' => '2026-10-01', 'valor_total' => 'R$ 1.200,50',
        ])->assertSessionHasNoErrors();
        $this->assertEqualsWithDelta(1200.50, (float) $cartao->faturas()->sole()->valor_total, 0.001);

        $divida = $user->dividas()->create([
            'descricao' => 'Empréstimo', 'valor_principal' => 1000, 'credor' => 'Banco X',
            'data_inicio' => '2026-01-01', 'saldo_devedor_atual' => 1000,
        ]);
        $this->post("/dividas/{$divida->id}/parcelas", [
            'numero_parcela' => '1', 'valor' => 'R$ 250,00', 'data_vencimento' => '2026-10-15',
        ])->assertSessionHasNoErrors();
        $this->assertEqualsWithDelta(250, (float) $divida->parcelas()->sole()->valor, 0.001);
    }

    public static function valoresInvalidos(): array
    {
        return [
            'texto' => ['abc', 'O campo valor principal deve ser um número.'],
            'duas virgulas' => ['1,2,3', 'O campo valor principal deve ser um número.'],
            'tres casas' => ['10,555', 'O campo valor principal deve ter no máximo 2 casas decimais.'],
            'zero' => ['R$ 0,00', 'O valor principal deve ser maior que zero.'],
            'vazio' => ['', 'O campo valor principal é obrigatório.'],
        ];
    }

    #[DataProvider('valoresInvalidos')]
    public function test_valor_invalido_e_recusado_em_portugues(string $digitado, string $mensagem): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/dividas', [
            'descricao' => 'Empréstimo', 'credor' => 'Banco X',
            'valor_principal' => $digitado, 'data_inicio' => '2026-01-10',
        ])->assertSessionHasErrors(['valor_principal' => $mensagem]);

        $this->assertSame(0, $user->dividas()->count());
    }

    public function test_taxa_mascarada_e_limitada_a_100(): void
    {
        $user = User::factory()->create();
        $divida = $user->dividas()->create([
            'descricao' => 'Empréstimo', 'valor_principal' => 1000, 'credor' => 'Banco X',
            'data_inicio' => '2026-01-01', 'saldo_devedor_atual' => 1000,
        ]);

        $this->actingAs($user)->post("/dividas/{$divida->id}/juros", ['mes_referencia' => '2026-01-01', 'taxa_juros' => '100,00'])
            ->assertSessionHasNoErrors();
        $this->post("/dividas/{$divida->id}/juros", ['mes_referencia' => '2026-02-01', 'taxa_juros' => '100,01'])
            ->assertSessionHasErrors(['taxa_juros' => 'A taxa de juros deve estar entre 0% e 100%.']);
    }
}
