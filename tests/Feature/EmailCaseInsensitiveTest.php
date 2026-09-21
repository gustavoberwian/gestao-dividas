<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmailCaseInsensitiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_e_sempre_salvo_em_minusculas(): void
    {
        $user = User::factory()->create(['email' => 'Fulano.DeTal@Exemplo.COM']);

        $this->assertSame('fulano.detal@exemplo.com', $user->email);
    }

    public function test_usuario_pode_logar_digitando_email_com_case_diferente_do_cadastro(): void
    {
        $user = User::factory()->create([
            'email' => 'usuario@exemplo.com',
            'password' => Hash::make('senha12345'),
        ]);

        $response = $this->post('/login', [
            'email' => 'USUARIO@Exemplo.com',
            'password' => 'senha12345',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/dashboard');
    }

    public function test_nao_e_possivel_cadastrar_dois_usuarios_com_mesmo_email_em_case_diferente(): void
    {
        User::factory()->create(['email' => 'duplicado@exemplo.com']);

        $response = $this->post('/register', [
            'name' => 'Outro Usuario',
            'email' => 'Duplicado@Exemplo.com',
            'password' => 'senha12345',
            'password_confirmation' => 'senha12345',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertSame(1, User::where('email', 'duplicado@exemplo.com')->count());
    }
}
