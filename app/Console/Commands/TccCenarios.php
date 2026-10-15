<?php

namespace App\Console\Commands;

use App\Models\CartaoCredito;
use App\Models\Divida;
use App\Models\Fatura;
use App\Models\HistoricoJuros;
use App\Models\Parcela;
use App\Models\User;
use App\Services\AmortizacaoService;
use Dotenv\Dotenv;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Evidencia do Capitulo 5 (SPEC_TESTES.md, Parte C), sem PHPUnit.
 *
 * Executa C1 a C6 em um schema temporario do banco de testes (criado e apagado pelo
 * proprio comando; o banco de desenvolvimento nao e tocado), imprime a tabela
 * Cenario | Esperado | Obtido | Diferenca | Status e salva em docs/resultado_cenarios.md.
 * Depois roda "php artisan test --testdox" e salva a saida em docs/resultado_testes.txt.
 */
class TccCenarios extends Command
{
    protected $signature = 'tcc:cenarios
        {--banco=gestao_dividas_test : Banco PostgreSQL onde o schema temporario e criado}
        {--sem-testes : Nao roda a suite PHPUnit (nao gera docs/resultado_testes.txt)}';

    protected $description = 'Executa os cenarios C1 a C6 do TCC e gera docs/resultado_cenarios.md e docs/resultado_testes.txt';

    private const TOLERANCIA = 0.01;

    /** @var array<int, array<string, string>> */
    private array $linhas = [];

    public function handle(): int
    {
        if (! str_ends_with((string) $this->option('banco'), '_test')) {
            $this->error('Recusado: --banco deve ser um banco de testes (nome terminado em "_test").');

            return self::FAILURE;
        }

        $schema = 'tcc_cenarios_'.Str::lower(Str::random(8));
        $conexaoOriginal = config('database.default');

        $this->info("Banco: {$this->option('banco')} · schema temporário: {$schema}");

        try {
            $this->prepararSchemaTemporario($schema);

            $this->cenariosAmortizacao();
            $this->cenarioC5();
            $this->cenarioC6();
        } catch (Throwable $e) {
            $this->error('Falha ao executar os cenários: '.$e->getMessage());

            return self::FAILURE;
        } finally {
            $this->apagarSchemaTemporario($schema, $conexaoOriginal);
        }

        $this->table(['Cenário', 'Esperado', 'Obtido', 'Diferença', 'Status'], $this->linhas);
        $arquivo = $this->salvarMarkdown();
        $this->info("Tabela salva em {$arquivo}");

        $falhas = collect($this->linhas)->where('status', '!=', 'OK')->count();

        if (! $this->option('sem-testes')) {
            $this->rodarSuite();
        }

        return $falhas === 0 ? self::SUCCESS : self::FAILURE;
    }

    // ---------------------------------------------------------------------
    // Banco temporario
    // ---------------------------------------------------------------------

    private function prepararSchemaTemporario(string $schema): void
    {
        config(['database.connections.tcc_cenarios' => array_merge(config('database.connections.pgsql'), [
            'database' => $this->option('banco'),
            'search_path' => $schema,
        ])]);

        DB::connection('tcc_cenarios')->statement("CREATE SCHEMA \"{$schema}\"");

        config(['database.default' => 'tcc_cenarios']);
        DB::setDefaultConnection('tcc_cenarios');

        Artisan::call('migrate', ['--database' => 'tcc_cenarios', '--force' => true]);
    }

    private function apagarSchemaTemporario(string $schema, string $conexaoOriginal): void
    {
        try {
            DB::connection('tcc_cenarios')->statement("DROP SCHEMA IF EXISTS \"{$schema}\" CASCADE");
            $this->info("Schema temporário {$schema} apagado.");
        } catch (Throwable $e) {
            $this->warn("Não foi possível apagar o schema {$schema}: ".$e->getMessage());
        }

        DB::purge('tcc_cenarios');
        config(['database.default' => $conexaoOriginal]);
        DB::setDefaultConnection($conexaoOriginal);
    }

    // ---------------------------------------------------------------------
    // C1 a C4: AmortizacaoService::calcularSaldoDevedor mes a mes
    // ---------------------------------------------------------------------

    private function cenariosAmortizacao(): void
    {
        $cenarios = [
            'C1' => [10000.00, [['2026-01', 0.015, 1000.00], ['2026-02', 0.012, 1000.00], ['2026-03', 0.010, 1000.00]], [9150.00, 8259.80, 7342.40]],
            'C2' => [5000.00, [['2026-01', 0.020, 0.0], ['2026-02', 0.020, 0.0]], [5100.00, 5202.00]],
            'C3' => [1000.00, [['2026-01', 0.015, 1015.00]], [0.00]],
            'C4' => [2000.00, [['2026-01', 0.000, 500.00]], [1500.00]],
        ];

        $servico = app(AmortizacaoService::class);

        foreach ($cenarios as $nome => [$principal, $meses, $esperados]) {
            $divida = Divida::create([
                'user_id' => User::factory()->create()->id,
                'descricao' => "Cenário {$nome}", 'valor_principal' => $principal, 'credor' => 'Banco',
                'data_inicio' => '2026-01-01', 'saldo_devedor_atual' => $principal,
            ]);

            foreach ($meses as $i => [$mes, $taxa, $pagamento]) {
                HistoricoJuros::create([
                    'divida_id' => $divida->id, 'mes_referencia' => "{$mes}-01",
                    'taxa_juros' => $taxa, 'saldo_devedor_calculado' => 0,
                ]);
                if ($pagamento > 0) {
                    Parcela::create([
                        'divida_id' => $divida->id, 'numero_parcela' => $i + 1, 'valor' => $pagamento,
                        'data_vencimento' => "{$mes}-10", 'status_pagamento' => 'paga',
                    ]);
                }

                $obtido = $servico->calcularSaldoDevedor($divida, "{$mes}-01");
                $rotulo = count($meses) > 1 ? "{$nome} — mês ".($i + 1) : $nome;
                $this->registrarValor($rotulo, $esperados[$i], $obtido);
            }
        }
    }

    // ---------------------------------------------------------------------
    // C5: Renda Livre Real exibida pelo GET /dashboard
    // ---------------------------------------------------------------------

    private function cenarioC5(): void
    {
        $hoje = Carbon::today();
        $fimDoMes = $hoje->copy()->endOfMonth()->toDateString();

        $user = User::factory()->create(['renda_mensal' => 5000]);
        $cartao = CartaoCredito::create(['user_id' => $user->id, 'nome_cartao' => 'Cartão', 'limite' => 5000, 'dia_fechamento' => 3, 'dia_vencimento' => 10]);
        $faturaAberta = Fatura::create(['cartao_credito_id' => $cartao->id, 'mes_referencia' => $hoje->copy()->startOfMonth()->toDateString(), 'valor_total' => 1200, 'status_pagamento' => 'aberta']);
        $divida = Divida::create(['user_id' => $user->id, 'descricao' => 'C5', 'valor_principal' => 5000, 'credor' => 'Banco', 'data_inicio' => '2026-01-01', 'saldo_devedor_atual' => 5000]);
        Parcela::create(['divida_id' => $divida->id, 'numero_parcela' => 1, 'valor' => 500, 'data_vencimento' => $fimDoMes, 'status_pagamento' => 'pendente']);
        Parcela::create(['divida_id' => $divida->id, 'numero_parcela' => 2, 'valor' => 300, 'data_vencimento' => $fimDoMes, 'status_pagamento' => 'pendente']);

        $this->registrarValor('C5', 3000.00, $this->rendaLivreReal($user));

        // casos de borda que nao podem alterar o resultado
        Parcela::create(['fatura_id' => $faturaAberta->id, 'numero_parcela' => 1, 'valor' => 700, 'data_vencimento' => $fimDoMes, 'status_pagamento' => 'pendente']);
        Fatura::create(['cartao_credito_id' => $cartao->id, 'mes_referencia' => $hoje->copy()->subMonth()->startOfMonth()->toDateString(), 'valor_total' => 999, 'status_pagamento' => 'paga']);
        Parcela::create(['divida_id' => $divida->id, 'numero_parcela' => 3, 'valor' => 150, 'data_vencimento' => $fimDoMes, 'status_pagamento' => 'paga']);
        Parcela::create(['divida_id' => $divida->id, 'numero_parcela' => 4, 'valor' => 400, 'data_vencimento' => $hoje->copy()->addMonthNoOverflow()->endOfMonth()->toDateString(), 'status_pagamento' => 'pendente']);

        $this->registrarValor('C5 + bordas (a)–(d)', 3000.00, $this->rendaLivreReal($user));
    }

    private function rendaLivreReal(User $user): float
    {
        Auth::forgetGuards();
        Auth::guard('web')->setUser($user);

        $response = $this->requisicao('GET', '/dashboard');

        return (float) $response->original->getData()['saldoDisponivel'];
    }

    // ---------------------------------------------------------------------
    // C6: usuario B tenta acessar dados do usuario A
    // ---------------------------------------------------------------------

    private function cenarioC6(): void
    {
        // CSRF desligado apenas nas requisicoes internas deste comando (o teste e de autorizacao)
        ValidateCsrfToken::except(['*']);

        $a = User::factory()->create();
        $b = User::factory()->create();
        $divida = Divida::create(['user_id' => $a->id, 'descricao' => 'Dívida de A', 'valor_principal' => 1000, 'credor' => 'Banco', 'data_inicio' => '2026-01-01', 'saldo_devedor_atual' => 1000]);
        $parcela = Parcela::create(['divida_id' => $divida->id, 'numero_parcela' => 1, 'valor' => 100, 'data_vencimento' => '2026-01-10', 'status_pagamento' => 'pendente']);
        $cartao = CartaoCredito::create(['user_id' => $a->id, 'nome_cartao' => 'Cartão de A', 'limite' => 1000, 'dia_fechamento' => 3, 'dia_vencimento' => 10]);
        $fatura = Fatura::create(['cartao_credito_id' => $cartao->id, 'mes_referencia' => '2026-01-01', 'valor_total' => 100, 'status_pagamento' => 'aberta']);

        $rotas = [
            ['GET', "/dividas/{$divida->id}", [], [403, 404, 405]],
            ['GET', "/dividas/{$divida->id}/relatorio", [], [403, 404]],
            ['POST', "/dividas/{$divida->id}/juros", ['mes_referencia' => '2026-01-01', 'taxa_juros' => '1,5'], [403, 404]],
            ['PATCH', "/parcelas/{$parcela->id}/pagar", [], [403, 404]],
            ['PATCH', "/faturas/{$fatura->id}/pagar", [], [403, 404]],
            ['GET', "/cartoes/{$cartao->id}", [], [403, 404]],
        ];

        Auth::forgetGuards();
        Auth::guard('web')->setUser($b);
        foreach ($rotas as [$metodo, $url, $corpo, $negado]) {
            $status = $this->requisicao($metodo, $url, $corpo)->getStatusCode();
            $esperado = in_array(405, $negado, true) ? 'negado (rota inexistente: 405)' : 'negado (403/404)';
            $this->registrarAcesso("C6 — B: {$metodo} {$url}", $esperado, $status, in_array($status, $negado, true));
        }

        // listagens e dashboard de B nao mostram dados de A
        foreach (['/dividas', '/cartoes', '/dashboard'] as $url) {
            $response = $this->requisicao('GET', $url);
            $vazou = str_contains($response->getContent(), 'Dívida de A') || str_contains($response->getContent(), 'Cartão de A');
            $this->registrarAcesso("C6 — B: listagem {$url}", 'sem dados de A', $response->getStatusCode().($vazou ? ' (com dados de A)' : ' (sem dados de A)'), ! $vazou && $response->getStatusCode() === 200);
        }

        // visitante: redireciona para o login
        Auth::forgetGuards();
        foreach (array_slice($rotas, 1) as [$metodo, $url, $corpo]) {
            $response = $this->requisicao($metodo, $url, $corpo);
            $status = $response->getStatusCode();
            $paraLogin = $status === 302 && str_ends_with((string) $response->headers->get('Location'), '/login');
            $this->registrarAcesso("C6 — visitante: {$metodo} {$url}", 'login (302)', $status, $paraLogin);
        }
    }

    private function requisicao(string $metodo, string $url, array $corpo = [])
    {
        $kernel = app(HttpKernel::class);
        $request = Request::create($url, $metodo, $corpo);
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        return $response;
    }

    // ---------------------------------------------------------------------
    // Saida
    // ---------------------------------------------------------------------

    private function registrarValor(string $cenario, float $esperado, float $obtido): void
    {
        $diferenca = round($obtido - $esperado, 2);

        $this->linhas[] = [
            'cenario' => $cenario,
            'esperado' => $this->moeda($esperado),
            'obtido' => $this->moeda($obtido),
            'diferenca' => $this->moeda($diferenca),
            'status' => abs($diferenca) <= self::TOLERANCIA ? 'OK' : 'FALHOU',
        ];
    }

    private function registrarAcesso(string $cenario, string $esperado, int|string $obtido, bool $ok): void
    {
        $this->linhas[] = [
            'cenario' => $cenario,
            'esperado' => $esperado,
            'obtido' => (string) $obtido,
            'diferenca' => '—',
            'status' => $ok ? 'OK' : 'FALHOU',
        ];
    }

    private function moeda(float $valor): string
    {
        return number_format($valor, 2, ',', '.');
    }

    private function salvarMarkdown(): string
    {
        $linhas = collect($this->linhas)
            ->map(fn ($l) => "| {$l['cenario']} | {$l['esperado']} | {$l['obtido']} | {$l['diferenca']} | {$l['status']} |")
            ->implode("\n");
        $ok = collect($this->linhas)->where('status', 'OK')->count();
        $total = count($this->linhas);

        $conteudo = <<<MD
        # Resultado dos cenários C1 a C6

        Gerado por `php artisan tcc:cenarios` em {$this->agora()}.
        Ambiente: PHP {$this->versaoPhp()} · Laravel {$this->laravel->version()} · {$this->versaoBanco()}.
        Execução sem PHPUnit, em um schema temporário do banco `{$this->option('banco')}` (criado e apagado pelo comando).

        Fórmula: `SD = SD_anterior × (1 + i) − P`, arredondada a 2 casas a cada mês.
        Critério: `|diferença| ≤ 0,01`. C6: esperado acesso negado (403/404) e redirecionamento do visitante ao login.

        **Resultado: {$ok} de {$total} verificações OK.**

        | Cenário | Esperado | Obtido | Diferença | Status |
        |---|---|---|---|---|
        {$linhas}

        Observações:
        - C5 + bordas (a)–(d): (a) parcela vinculada a fatura; (b) fatura paga; (c) parcela de dívida já paga;
          (d) parcela de dívida com vencimento em outro mês. Nenhum deles pode alterar a Renda Livre Real.
        - `GET /dividas/{id}` não existe no sistema (a tela da dívida é `/dividas/{id}/relatorio`); nessa URI só há
          PUT/DELETE, por isso o retorno 405 também é aceito como "negado".

        MD;

        File::ensureDirectoryExists(base_path('docs'));
        File::put(base_path('docs/resultado_cenarios.md'), $conteudo);

        return 'docs/resultado_cenarios.md';
    }

    private function rodarSuite(): void
    {
        $this->info('Rodando php artisan test --testdox (pode levar alguns segundos)...');

        $ambiente = $this->ambienteDeTeste();
        if ($ambiente === null) {
            return;
        }

        $processo = new Process([PHP_BINARY, 'artisan', 'test', '--testdox', '--colors=never'], base_path(), $ambiente, null, 600);
        $processo->run();

        $saida = preg_replace('/\e\[[\d;]*m/', '', $processo->getOutput().$processo->getErrorOutput());
        File::put(base_path('docs/resultado_testes.txt'), "# php artisan test --testdox — {$this->agora()}\n\n".$saida);

        $resumo = collect(explode("\n", $saida))->first(fn ($linha) => str_contains($linha, 'Tests:'));
        $this->info('Suíte: '.trim((string) $resumo).' → docs/resultado_testes.txt');
    }

    /**
     * Ambiente do subprocesso de testes. O processo atual exporta (putenv) as variaveis do
     * .env de desenvolvimento, e o filho as herdaria: como o .env.testing nao sobrescreve
     * variaveis existentes, os testes rodariam no banco de desenvolvimento. Por isso as
     * variaveis do .env sao removidas (false) e as do .env.testing passadas explicitamente.
     *
     * @return array<string, string|false>|null
     */
    private function ambienteDeTeste(): ?array
    {
        $arquivoTeste = base_path('.env.testing');
        if (! File::exists($arquivoTeste)) {
            $this->error('Arquivo .env.testing não encontrado; a suíte não foi executada.');

            return null;
        }

        $teste = Dotenv::parse(File::get($arquivoTeste));
        if (! str_ends_with((string) ($teste['DB_DATABASE'] ?? ''), '_test')) {
            $this->error('Recusado: o DB_DATABASE do .env.testing não termina em "_test"; a suíte não foi executada.');

            return null;
        }

        $desenvolvimento = File::exists(base_path('.env')) ? Dotenv::parse(File::get(base_path('.env'))) : [];
        $remover = array_fill_keys(array_keys($desenvolvimento), false);

        return array_merge($remover, $teste, ['APP_ENV' => 'testing']);
    }

    private function agora(): string
    {
        return Carbon::now()->format('d/m/Y H:i');
    }

    private function versaoPhp(): string
    {
        return PHP_VERSION;
    }

    private function versaoBanco(): string
    {
        try {
            return 'PostgreSQL '.DB::connection('pgsql')->selectOne('SHOW server_version')->server_version;
        } catch (Throwable) {
            return 'PostgreSQL';
        }
    }
}
