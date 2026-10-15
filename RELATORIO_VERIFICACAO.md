# Relatório de verificação — artigo × código (gestao-dividas)

Base: commit `f217f84` (13 commits). Verificado lendo o código e **executando** a suíte em
PHP 8.3 + PostgreSQL 16 (ambiente da Anthropic, não o seu WSL), após `composer install` e `yarn build`.

## 1. Resultado dos testes

- Suíte existente: **69 testes, 62 passaram, 7 ignorados** (os 7 são recursos do Jetstream
  desativados: tokens de API, verificação de e-mail e "registro desativado"). Nenhuma falha.
  Obs.: sem rodar `yarn build` antes, 10 testes falham por falta do `manifest.json` do Vite.
- Cenários do artigo, rodados de ponta a ponta pelas rotas HTTP (cadastrar parcela → marcar paga →
  registrar taxa), com testes temporários que **não foram salvos no repositório**:

| Cenário | Esperado | Obtido | Status |
|---|---|---|---|
| C1 | 9.150,00 / 8.259,80 / 7.342,40 | 9150 / 8259,8 / 7342,4 | OK |
| C2 | 5.100,00 / 5.202,00 | 5100 / 5202 | OK |
| C3 | 0,00 | 0 | OK |
| C4 | 1.500,00 | 1500 | OK |
| C5 | 3.000,00 | 3000 (com 4 casos de borda que não entram na conta) | OK |
| C6 | 403 em todas as rotas de A para B | 403 em 6 de 6; listagens de B sem dados de A; visitante → 302 login | OK |
| CHECK parcelas | inserir fatura_id e divida_id → erro | `QueryException` | OK |

Casos de borda do C5 que não alteraram o resultado: parcela de fatura, parcela de dívida já paga,
parcela de dívida com vencimento em outro mês.

## 2. Afirmações do Capítulo 4 × código

| Afirmação | Situação |
|---|---|
| MVC + Service Layer (AmortizacaoService) | Confirmado |
| Fórmula SD = SD_ant·(1+i) − P; P = parcelas pagas com vencimento no mês | Confirmado (`AmortizacaoService`) |
| Recálculo disparado ao registrar taxa (RF005) e ao pagar parcela de dívida (RF006) | Confirmado |
| Arredondamento a 2 casas a cada mês | Confirmado (`round(...,2)`) |
| Valores armazenados em tipo exato (RNF003) | Confirmado (decimal/numérico). **Mas o cálculo usa `float`** e arredonda no fim; não afirmar "aritmética exata" |
| CHECK em `parcelas` (exatamente um de fatura/dívida) | Confirmado (`parcelas_fatura_ou_divida_check`) |
| Policies (RNF002) + filtro por usuário nas listagens | Confirmado: `DividaPolicy`, `CartaoCreditoPolicy`, `FaturaPolicy`, `ParcelaPolicy`; `authorize` em todas as rotas com modelo |
| Autenticação "nativa do Laravel" | **Diferente**: é **Laravel Jetstream (Livewire, sem Teams) + Fortify + Sanctum**. Corrigir o texto |
| Recuperação de senha por e-mail (RF010) | Existe (testes passam); depende de configurar e-mail para funcionar de fato |
| Git, Composer, Yarn, PHPUnit | Confirmado (`composer.lock`, `yarn.lock`, 69 testes) |
| Vuexy 10.11.1 | **Não confirmado no código** (o `package.json` do starter diz `3.0.0`). Conferir a versão na página da compra |
| Dashboard RLR = R − (F + D) | Confirmado, com detalhes abaixo |

Detalhes do dashboard (`DashboardController`):
- **F** = soma de **todas** as faturas com status `aberta`, de qualquer mês (não só do mês corrente).
- **D** = parcelas de dívida **pendentes** com vencimento no mês corrente.
- Parcelas de fatura e parcelas já pagas não entram.
- O texto do artigo deve dizer "faturas em aberto" (e não "do mês").

## 3. Defeitos e lacunas que afetam a coleta (corrigir antes de aplicar o teste)

1. **Não existe tela para cadastrar a renda mensal (RF002).** A rota `PUT /perfil/renda` e o
   controller existem, mas nenhuma view tem o formulário. Sem renda, o dashboard nunca mostra
   um valor útil. **Bloqueia a tarefa T1, o teste de interface do C5 e as perguntas U1/U2.**
2. **O dashboard não usa o termo "Renda Livre Real".** O cartão se chama "Saldo disponível".
   O questionário (U1, U2, T6) e o artigo usam "Renda Livre Real". Padronizar um termo só.
3. **A taxa de juros é digitada como fração** (`0.015`), segundo o rótulo "ex: 0.015 = 1,5%".
   O roteiro T3 diz "taxa de 1,5%". Quem digitar `1.5` obtém saldo de **R$ 24.000,00** em vez de
   R$ 9.150,00 (medido). Mudar o campo para percentual (e dividir por 100 no servidor) ou
   corrigir o roteiro. Percentual é mais natural para o participante.
4. **O recálculo não é em cascata.** Se uma parcela for paga depois de os meses seguintes já terem
   sido calculados, os meses posteriores ficam desatualizados. Não afeta C1 a C6, mas é uma
   limitação a registrar no artigo.
5. **T3 exige cadastrar a parcela antes de pagá-la** (a tela de relatório tem os dois formulários).
   Inclua isso no roteiro: "cadastrar parcela de R$ 1.000,00 com vencimento no mês corrente,
   marcá-la como paga e registrar a taxa de 1,5%".
6. **Interface em português só no sistema; telas do Jetstream/perfil podem ter textos em inglês**
   (idioma da aplicação está em `en`). Definir `APP_LOCALE=pt_BR` e checar as telas de login,
   registro e perfil antes de os participantes usarem.
7. **README ainda é o padrão do Laravel**, e o repositório agora é público. Vale escrever um README
   curto (o que é, como rodar). O `.env` não está versionado (correto).

## 4. Correções a fazer no artigo (versão final)

- 4.2: trocar "mecanismos nativos de autenticação do Laravel" por "Laravel Jetstream (stack
  Livewire, sem recurso de Teams), baseado no Fortify".
- 4.4 / RNF003: deixar claro que o **armazenamento** é exato e o **cálculo** usa ponto flutuante com
  arredondamento a duas casas por mês; incluir como limitação.
- 4.5: "faturas em aberto" (todas), "parcelas de dívidas pendentes no mês".
- Apêndice B / roteiro: ajustar T1 (existe a tela de renda?), T3 (parcela primeiro) e o termo
  "Renda Livre Real" conforme o que o sistema realmente exibir.
- Conferir a versão do Vuexy.
- Mencionar a quantidade real de testes (69, dos quais 4 arquivos são específicos do domínio)
  só depois de rodar de novo no seu ambiente.

## 5. Como reproduzir

```bash
pg_ctlcluster 16 main start   # ou o seu Postgres local
createdb gestao_dividas_test
cp .env.testing.example .env.testing   # ajustar usuário/senha e APP_KEY
composer install && yarn && yarn build
php artisan test --testdox
```
