# Especificação Técnica — Sistema de Gestão de Dívidas e Cartão de Crédito

> Este documento é a especificação de implementação, derivada do artigo científico
> (Projeto Final II — Etapa 1). Serve como brief para desenvolvimento via Claude Code.

## 0. Como aplicar esta spec

Este projeto é **single-tenant multiusuário** (ver seção 1.1) — não é um SaaS multi-organização.
Um banco só, cada linha de dado pertence a um `user_id`.

Como você já tem outros projetos rodando no WSL, cole este prompt inicial no Claude Code CLI,
dentro de uma pasta nova e vazia:

> "Leia o arquivo SPEC.md nesta pasta. Crie um novo projeto Laravel aqui, configurado para
> PostgreSQL. Antes de rodar qualquer coisa, verifique se as portas 5432 (Postgres) e 8000
> (servidor Laravel) já estão em uso por outros projetos no meu WSL — se estiverem, use portas
> alternativas e me avise quais escolheu. Inicialize um repositório git com .gitignore padrão
> do Laravel, crie um .env.example com os valores de conexão (sem senha real), e configure o
> PHPUnit para rodar os testes que vamos criar para o AmortizacaoService. Depois disso, pare e
> me mostre a estrutura antes de seguir para as migrations."

Isso garante que ele não pisa em outro projeto seu por engano, e que você revisa o esqueleto
antes de avançar. Depois disso, siga a ordem da seção 6, pedindo passo a passo — não peça
"implemente tudo" de uma vez; peça uma seção da spec por vez, revisando entre elas.

## 1. Stack

- **Backend:** Laravel (PHP), arquitetura MVC
- **Banco de dados:** PostgreSQL
- **Frontend:** Blade + template comercial HTML/CSS/JS já adquirido pelo autor, adaptado desde o início do projeto (ver seção 5 sobre como integrar)
- **Autenticação:** Laravel padrão (sessão, `Auth` facade), sem OAuth por ora

### 1.1 Modelo de tenancy: single-tenant, multiusuário

Este **não é** um sistema multi-tenant. Não existem organizações, contas empresariais ou
administradores de tenant. É um único banco de dados compartilhado por todos os usuários,
onde cada registro sensível (`Divida`, `CartaoCredito`, `Fatura`, `Parcela`, `HistoricoJuros`)
pertence a exatamente um `user_id` — direta ou indiretamente (ex: `Fatura` pertence a um
`CartaoCredito`, que pertence a um `user_id`).

Isso significa que o RNF002 ("usuário não pode acessar dados financeiros de outro usuário")
**não precisa** de arquitetura de tenant — precisa de **autorização por linha**, implementada
com **Laravel Policies**, uma por model sensível:

```php
// app/Policies/DividaPolicy.php
public function view(User $user, Divida $divida): bool
{
    return $user->id === $divida->user_id;
}
```

Para `Fatura` e `Parcela` (que não têm `user_id` direto), a policy verifica a cadeia:

```php
// app/Policies/FaturaPolicy.php
public function view(User $user, Fatura $fatura): bool
{
    return $user->id === $fatura->cartaoCredito->user_id;
}
```

Registre todas as policies em `AuthServiceProvider` e use `$this->authorize('view', $divida)`
em todo controller que acessa esses models — isso sozinho implementa o RNF002 de forma
consistente, sem precisar repetir `where('user_id', auth()->id())` em cada query manualmente
(embora isso também ajude como camada extra: sempre filtre queries de listagem por
`auth()->id()`, não confie só na Policy para esconder registros de outros usuários em listagens).



## 2. Schema do Banco de Dados (Migrations)

### `users` (Usuario)
| Coluna | Tipo | Observação |
|---|---|---|
| id | bigint, PK | padrão Laravel |
| nome | string | |
| email | string, unique | |
| password | string | hash (Laravel padrão) |
| renda_mensal | decimal(12,2) | **nullable** até o usuário cadastrar (RF002) |
| created_at / updated_at | timestamps | |

### `cartoes_credito` (CartaoCredito)
| Coluna | Tipo | Observação |
|---|---|---|
| id | bigint, PK | |
| user_id | bigint, FK → users | |
| nome_cartao | string | |
| limite | decimal(12,2) | |
| dia_fechamento | smallint | 1-31 |
| dia_vencimento | smallint | 1-31 |
| created_at / updated_at | timestamps | |

### `faturas` (Fatura)
| Coluna | Tipo | Observação |
|---|---|---|
| id | bigint, PK | |
| cartao_credito_id | bigint, FK → cartoes_credito | |
| mes_referencia | date | primeiro dia do mês de referência |
| valor_total | decimal(12,2) | |
| status_pagamento | enum('aberta','paga') | default 'aberta' |
| created_at / updated_at | timestamps | |

### `dividas` (Divida)
| Coluna | Tipo | Observação |
|---|---|---|
| id | bigint, PK | |
| user_id | bigint, FK → users | |
| descricao | string | |
| valor_principal | decimal(12,2) | valor original da dívida |
| credor | string | |
| data_inicio | date | |
| saldo_devedor_atual | decimal(12,2) | **cache** do último SD calculado (evita recalcular toda vez) |
| created_at / updated_at | timestamps | |

### `parcelas` (Parcela)
| Coluna | Tipo | Observação |
|---|---|---|
| id | bigint, PK | |
| fatura_id | bigint, FK → faturas, **nullable** | |
| divida_id | bigint, FK → dividas, **nullable** | |
| numero_parcela | smallint | |
| valor | decimal(12,2) | |
| data_vencimento | date | |
| status_pagamento | enum('pendente','paga') | default 'pendente' |
| created_at / updated_at | timestamps | |
| **constraint** | CHECK | exatamente um de `fatura_id`/`divida_id` deve ser não-nulo (nunca ambos, nunca nenhum) |

### `historico_juros` (HistoricoJuros)
| Coluna | Tipo | Observação |
|---|---|---|
| id | bigint, PK | |
| divida_id | bigint, FK → dividas | |
| mes_referencia | date | |
| taxa_juros | decimal(6,4) | taxa mensal, ex: 0.0150 = 1,5% |
| saldo_devedor_calculado | decimal(12,2) | resultado da fórmula naquele mês |
| created_at / updated_at | timestamps | |

## 3. Motor de Cálculo de Amortização (núcleo do sistema)

Fórmula (já documentada no artigo, seção 2.2):

```
SD_atual = SD_anterior * (1 + i) - P
```

Onde:
- `SD_anterior` = saldo devedor do mês anterior (ou `valor_principal` se for o primeiro cálculo da dívida)
- `i` = taxa de juros do mês (`HistoricoJuros.taxa_juros` do mês corrente)
- `P` = soma das parcelas da dívida pagas naquele mês (`Parcela.valor` onde `status_pagamento = 'paga'` e `data_vencimento` no mês corrente)

**Pseudocódigo do serviço** (`App\Services\AmortizacaoService`):

```php
function calcularSaldoDevedor(Divida $divida, string $mesReferencia): float
{
    $historicoAnterior = HistoricoJuros::where('divida_id', $divida->id)
        ->where('mes_referencia', '<', $mesReferencia)
        ->orderByDesc('mes_referencia')
        ->first();

    $sdAnterior = $historicoAnterior
        ? $historicoAnterior->saldo_devedor_calculado
        : $divida->valor_principal;

    $historicoMesAtual = HistoricoJuros::where('divida_id', $divida->id)
        ->where('mes_referencia', $mesReferencia)
        ->first();

    $taxaJuros = $historicoMesAtual->taxa_juros ?? 0;

    $totalPago = Parcela::where('divida_id', $divida->id)
        ->where('status_pagamento', 'paga')
        ->whereYear('data_vencimento', ...)
        ->whereMonth('data_vencimento', ...)
        ->sum('valor');

    $sdAtual = $sdAnterior * (1 + $taxaJuros) - $totalPago;

    // Persistir em HistoricoJuros.saldo_devedor_calculado
    // Atualizar Divida.saldo_devedor_atual (cache)

    return $sdAtual;
}
```

**Disparo (RF007):** este cálculo deve ser recalculado sempre que:
1. Um novo registro em `HistoricoJuros` for criado para o mês corrente (RF005), OU
2. Uma `Parcela` vinculada a uma `Divida` for marcada como paga (RF006)

## 4. Mapeamento RF → Rotas/Controllers

| RF | Rota (sugestão) | Controller |
|---|---|---|
| RF001 | `POST /register`, `POST /login` | `Auth\*` (Laravel JetStream, sem Teams — seção 5.3) |
| RF002 | `PUT /perfil/renda` | `PerfilController@atualizarRenda` |
| RF003 | `POST /cartoes` | `CartaoCreditoController@store` |
| RF004 | `POST /dividas` | `DividaController@store` |
| RF005 | `POST /dividas/{divida}/juros` | `HistoricoJurosController@store` → dispara `AmortizacaoService` |
| RF006 | `PATCH /parcelas/{parcela}/pagar` | `ParcelaController@marcarPaga` → dispara `AmortizacaoService` se `divida_id` |
| RF007 | (interno, chamado pelo `AmortizacaoService`) | — |
| RF008 | `GET /dashboard` | `DashboardController@index` (renda_mensal − faturas abertas − parcelas do mês) |
| RF009 | `GET /dividas/{divida}/relatorio` | `DividaController@relatorioAmortizacao` |
| RF010 | `POST /forgot-password` | `Auth\PasswordResetController` (vem pronto com o JetStream) |
| RF011 | `POST /faturas`, `PATCH /faturas/{fatura}/pagar` | `FaturaController@store` / `@marcarPaga` |

## 5. Integrando o template comercial (Vuexy)

Seu Vuexy **já inclui uma versão pronta para Laravel** — não é preciso portar HTML estático manualmente. Dentro de `~/templates/vuexy/html-laravel-version/Bootstrap5/` existem duas versões: **starter-kit** (mínima, é a que estamos usando) e **full-version** (com todos os componentes/páginas de exemplo, para copiar trechos conforme precisar). A documentação oficial deles recomenda explicitamente **começar o projeto a partir do starter-kit**, em vez de criar um Laravel vazio e encaixar o Vuexy depois.

Isso muda o passo 1 da seção 6: **não** é `composer create-project laravel/laravel`. É usar o starter-kit do Vuexy como base do projeto.

### 5.1 Importante: o starter-kit já está copiado — só falta instanciar o projeto

Você já tem uma cópia de referência completa do Vuexy (todos os frameworks/versões) em `~/templates/vuexy`, fora do `/mnt/c/` — ótimo, resolve de vez o problema de performance do WSL. O starter-kit Laravel está em:

```
~/templates/vuexy/html-laravel-version/Bootstrap5/starter-kit
```

Copie esse starter-kit para a pasta do projeto em si (mantenha `~/templates/vuexy` como referência intocada, para poder voltar lá e copiar trechos do full-version depois):

```bash
mkdir -p ~/tcc
cp -r ~/templates/vuexy/html-laravel-version/Bootstrap5/starter-kit/. ~/tcc/gestao-dividas
cd ~/tcc/gestao-dividas
```

(usando `/.` no final da origem, não `/*` — evita o problema de expansão de glob com arquivos ocultos como `.env.example`, `.gitignore`)

### 5.2 Instalação (comandos oficiais do Vuexy)

Requisitos: Node ≥18.12, PHP ≥8.2, Composer ≥2.2, Laravel ≥12 — confirme com `php -v`, `node -v`, `composer -V` antes de começar.

```bash
composer install
cp .env.example .env
php artisan key:generate

# ajustar o .env para PostgreSQL (o exemplo padrão do Vuexy vem com MySQL)
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=gestao_dividas
# DB_USERNAME=gustavo
# DB_PASSWORD=senha_local_dev

yarn
yarn build
php artisan migrate
php artisan serve
```

Se der erro de permissão em `storage` ou `bootstrap/cache`:
```bash
sudo chmod -R o+rw bootstrap/cache
sudo chmod -R o+rw storage
```

### 5.3 Autenticação: usar o JetStream que o Vuexy já documenta

O Vuexy tem um guia próprio de integração com **Laravel JetStream** para autenticação — como RF001 (login/cadastro) e RF010 (recuperação de senha) são exatamente isso, faz mais sentido seguir o caminho que o próprio template já documenta em vez de instalar Breeze/Fortify por conta (evita conflito de scaffolding). O JetStream tem uma opção de "Teams" (times/organizações) que você **não vai usar** — lembre disso na hora de instalar (desative teams), já que este projeto é single-tenant (seção 1.1), não precisa dessa camada.

### 5.4 Depois de rodando: como criar as telas do sistema

Com o starter-kit no ar, cada nova tela do sistema (cadastro de dívida, dashboard, etc.) vira uma view Blade que estende o layout do Vuexy:

```php
@extends('layouts/layoutMaster')

@section('title', 'Minhas Dívidas')

@section('content')
    {{-- conteúdo da página aqui --}}
@endsection
```

Depois de criar uma página nova, registre o link dela no menu lateral, em `resources/menu/verticalMenu.json` — senão ela existe mas não aparece na navegação.

> Quando chegar nessa parte, se quiser, me diga o nome de uma página existente em `~/templates/vuexy/html-laravel-version/Bootstrap5/full-version` que se pareça com o que você precisa (ex: uma tela de listagem de transações, um formulário de cadastro) — aí eu te ajudo a adaptar aquele exemplo específico em vez de partir do zero.


## 6. Ordem de implementação sugerida

1. Copiar o starter-kit do Vuexy (`~/templates/vuexy/html-laravel-version/Bootstrap5/starter-kit`) para a pasta do projeto e instalar (seção 5.1-5.2) — isso já entrega um projeto Laravel rodando, com o template integrado desde o primeiro commit
2. Migrations (seção 2, na ordem: users → cartoes_credito → dividas → faturas → parcelas → historico_juros)
3. Configurar autenticação via JetStream (seção 5.3), desativando o módulo de Teams
4. Models + relationships (Eloquent) + o `AmortizacaoService`
5. Testes unitários do `AmortizacaoService` (crítico — é o coração do projeto, testar antes de integrar UI)
6. Controllers + rotas (tabela da seção 4)
7. Views Blade estendendo o layout do Vuexy (seção 5.4), registrando cada nova tela no menu
8. Dashboard (RF008), reaproveitando os gráficos que já vêm no template (ApexCharts/ChartJS)
