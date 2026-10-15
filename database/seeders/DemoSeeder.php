<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Usuario de demonstracao para o teste de uso (A5). Uso opcional:
 *
 *   php artisan db:seed --class=DemoSeeder
 *
 * Recria o usuario do zero a cada execucao (sem renda, dividas ou cartoes), para que
 * cada sessao com participante comece do mesmo estado. Nao e chamado pelo DatabaseSeeder.
 */
class DemoSeeder extends Seeder
{
    public const EMAIL = 'demo@gestaodividas.local';

    public const SENHA = 'demo12345';

    public function run(): void
    {
        // as FKs com cascadeOnDelete removem dividas, cartoes, faturas, parcelas e historico
        User::where('email', self::EMAIL)->delete();

        User::create([
            'name' => 'Usuário de Demonstração',
            'email' => self::EMAIL,
            'password' => self::SENHA,
            'renda_mensal' => null,
            'email_verified_at' => now(),
        ]);

        $this->command?->info('Usuário de demonstração: '.self::EMAIL.' / senha: '.self::SENHA);
    }
}
