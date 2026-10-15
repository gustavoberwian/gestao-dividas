<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class IdiomaEEmailTest extends TestCase
{
    use RefreshDatabase;

    // Bloco C: idioma e fuso padrao da aplicacao
    public function test_aplicacao_esta_em_portugues_e_no_fuso_de_sao_paulo(): void
    {
        $this->assertSame('pt_BR', app()->getLocale());
        $this->assertSame('America/Sao_Paulo', config('app.timezone'));
        $this->get('/login')->assertSee('<html lang="pt-BR"', false);
    }

    // Bloco C: mensagens de validacao em portugues, com o nome do campo traduzido
    public function test_mensagem_de_validacao_em_portugues(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/perfil/renda', ['renda_mensal' => ''])
            ->assertSessionHasErrors(['renda_mensal' => 'O campo renda mensal é obrigatório.']);
    }

    public function test_login_invalido_mostra_mensagem_em_portugues(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'senha-errada'])
            ->assertSessionHasErrors(['email' => 'E-mail ou senha incorretos.']);
    }

    // Bloco H / RF010: pedir o link envia a notificacao ao usuario certo, em portugues
    public function test_pedido_de_redefinicao_envia_email_ao_usuario_certo_em_portugues(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $outro = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHas('status', 'Enviamos por e-mail o link para redefinir sua senha.');

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notificacao) use ($user) {
            $mail = $notificacao->toMail($user);

            return $mail->subject === 'Redefinição de senha'
                && $mail->actionText === 'Redefinir senha'
                && str_contains($mail->introLines[0], 'pedido de redefinição de senha');
        });
        Notification::assertNotSentTo($outro, ResetPassword::class);
    }

    public function test_remetente_configurado(): void
    {
        $this->assertSame('nao-responder@gestaodividas.local', config('mail.from.address'));
        $this->assertSame('Gestão de Dívidas', config('mail.from.name'));
    }
}
