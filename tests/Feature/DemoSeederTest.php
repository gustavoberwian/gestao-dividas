<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    // A5: o seeder recria o usuario de demonstracao sem dados e ele consegue entrar
    public function test_demo_seeder_recria_usuario_limpo_que_consegue_fazer_login(): void
    {
        $this->seed(DemoSeeder::class);
        $antigo = User::where('email', DemoSeeder::EMAIL)->firstOrFail();
        $antigo->dividas()->create([
            'descricao' => 'Sobra da sessao anterior', 'valor_principal' => 100, 'credor' => 'X',
            'data_inicio' => '2026-01-01', 'saldo_devedor_atual' => 100,
        ]);

        $this->seed(DemoSeeder::class);

        $demo = User::where('email', DemoSeeder::EMAIL)->sole();
        $this->assertNull($demo->renda_mensal);
        $this->assertSame(0, $demo->dividas()->count());
        $this->assertDatabaseMissing('dividas', ['descricao' => 'Sobra da sessao anterior']);

        $this->post('/login', ['email' => DemoSeeder::EMAIL, 'password' => DemoSeeder::SENHA]);
        $this->assertAuthenticatedAs($demo);
    }

    // A5: o seeder so mexe no usuario demo; outros usuarios e seus dados ficam intactos
    public function test_demo_seeder_nao_apaga_outros_usuarios_nem_seus_dados(): void
    {
        $outro = User::factory()->create();
        $outro->dividas()->create([
            'descricao' => 'Dívida de outro usuário', 'valor_principal' => 500, 'credor' => 'Y',
            'data_inicio' => '2026-01-01', 'saldo_devedor_atual' => 500,
        ]);
        $outro->cartoesCredito()->create([
            'nome_cartao' => 'Cartão de outro usuário', 'limite' => 1000, 'dia_fechamento' => 1, 'dia_vencimento' => 10,
        ]);

        $this->seed(DemoSeeder::class);
        $this->seed(DemoSeeder::class);

        $this->assertDatabaseHas('users', ['id' => $outro->id]);
        $this->assertSame(1, $outro->dividas()->count());
        $this->assertSame(1, $outro->cartoesCredito()->count());
        $this->assertSame(2, User::count());
    }
}
