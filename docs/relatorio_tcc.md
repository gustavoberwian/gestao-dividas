# Relatório de testes — evidência para o Capítulo 5

Projeto: Gestão de Dívidas (Laravel 12 · Jetstream/Livewire · PostgreSQL 16 · PHP 8.3).
Data da execução: 15/10/2026. Arquivos de origem desta síntese:

- `docs/resultado_testes.txt`: saída de `php artisan test --testdox`
- `docs/resultado_cenarios.md`: tabela gerada por `php artisan tcc:cenarios` (C1 a C6, sem PHPUnit)

## 1. Resultado geral

| Indicador | Valor |
|---|---|
| Testes executados | **221** |
| Passaram | **214** |
| Ignorados (skipped) | 7 |
| Falharam | **0** |
| Asserções | 775 |
| Cenários do `tcc:cenarios` | **23 de 23 OK** (C1–C5 com diferença 0,00; C6 com acesso negado em todas as rotas) |

Os 7 testes ignorados são do próprio Jetstream e cobrem recursos **desativados** no sistema
(tokens de API, verificação de e-mail e "registro desativado"). Eles se ignoram sozinhos quando o
recurso está desligado; não são falhas.

## 2. Cenários do artigo

Fórmula: `SD = SD_anterior × (1 + i) − P`, com arredondamento a 2 casas a cada mês. Critério de aceitação: `|diferença| ≤ 0,01`.

| Cenário | Esperado | Obtido | Status | Onde é testado |
|---|---|---|---|---|
| C1 (3 meses) | 9.150,00 / 8.259,80 / 7.342,40 | 9.150,00 / 8.259,80 / 7.342,40 | OK | `tests/Unit/Services/CenariosC1C4Test.php`; fluxo HTTP em `tests/Feature/FluxoCompletoC1Test.php` |
| C2 (2 meses) | 5.100,00 / 5.202,00 | 5.100,00 / 5.202,00 | OK | `CenariosC1C4Test` |
| C3 | 0,00 | 0,00 | OK | `CenariosC1C4Test` |
| C4 | 1.500,00 | 1.500,00 | OK | `CenariosC1C4Test` |
| C5 | Renda Livre Real 3.000,00 | 3.000,00 | OK | `tests/Feature/RendaLivreRealTest.php` |
| C5, casos de borda (a)–(d) | 3.000,00 (sem alteração) | 3.000,00 | OK | `RendaLivreRealTest` |
| C6 | acesso negado; nenhum dado de A | 403 em todas as rotas de A; listagens de B sem dados de A; visitante → login | OK | `tests/Feature/IsolamentoEntreUsuariosTest.php` |
| CHECK `parcelas` | fatura e dívida juntas → erro; nenhuma → erro; exatamente uma → aceita | conforme esperado | OK | `tests/Feature/IntegridadeBancoTest.php` |

Casos de borda do C5, que **não** alteraram o resultado:
- (a) parcela vinculada a fatura;
- (b) fatura paga;
- (c) parcela de dívida já paga;
- (d) parcela de dívida com vencimento em outro mês.

Também foi verificado que uma parcela vencida de mês anterior gera alerta no dashboard sem entrar na fórmula.

Nos cenários C1 a C4 também foi conferido que `HistoricoJuros.saldo_devedor_calculado` e o cache
`Divida.saldo_devedor_atual` ficam gravados com o mesmo valor.

### Recálculo em cascata (Bloco G)

Os três cálculos de aceitação, feitos à mão a partir do C1 e executados pelas rotas HTTP em `tests/Feature/RecalculoCascataTest.php`, bateram:

| Operação | Esperado | Obtido |
|---|---|---|
| Editar o pagamento do mês 1 para 500,00 | 9.650,00 / 8.765,80 / 7.853,46 | idem |
| Excluir a taxa do mês 2 (mês com pagamento → 0,00%) | 9.150,00 / 8.150,00 / 7.231,50 | idem |
| Desfazer o pagamento do mês 3 | mês 3 = 8.342,40 | idem |

O mesmo arquivo verifica:
- editar a taxa;
- excluir parcela paga;
- excluir taxa de mês sem pagamento (o registro é removido);
- editar o valor principal (recalcula todos os meses);
- a permanência do registro de 0,00% criado ao pagar uma parcela;
- que meses sem taxa não geram juros;
- que uma falha no recálculo desfaz a operação inteira, porque edição e recálculo rodam na mesma transação.

## 3. Testes por arquivo (domínio do sistema)

| Arquivo | Testes | O que cobre |
|---|---|---|
| `Unit/Services/CenariosC1C4Test` | 5 | C1–C4 (RF005–RF007, RNF003) |
| `Unit/Services/AmortizacaoServiceTest` | 11 | serviço de amortização (já existente) |
| `Unit/Models/SituacaoTest` | 14 | situação derivada de parcela/fatura, limites de data e fuso de São Paulo |
| `Feature/RendaLivreRealTest` | 9 | C5, casos de borda e alerta de vencidas (RF008) |
| `Feature/IsolamentoEntreUsuariosTest` | 18 | C6 (RNF002) |
| `Feature/IntegridadeBancoTest` | 4 | CHECK de `parcelas` no PostgreSQL |
| `Feature/FluxoCompletoC1Test` | 1 | tarefas T1–T6 pelas rotas HTTP (49 asserções) |
| `Feature/RecalculoCascataTest` | 11 | cascata do Bloco G |
| `Feature/EditarExcluirTest` | 55 | cada rota de editar/excluir/desfazer: dono 302, outro usuário 403, visitante → login, filho de outro pai na URL aninhada → 404 |
| `Feature/MascarasTest` | 15 | formatos mascarados aceitos e recusas em português |
| `Feature/DashboardPainelTest` | 4 | alertas, barra de renda, próximos vencimentos, juros × amortização |
| Demais (controllers, idioma, avisos, identidade visual, seeder, Jetstream) | 74 | inclui os 7 ignorados do Jetstream |

## 4. Divergências entre o artigo/spec e o código (Parte A)

Levantadas no `RELATORIO_VERIFICACAO.md` e nesta etapa:

1. **Autenticação**: é Laravel **Jetstream** (stack Livewire, sem Teams), baseado em Fortify e Sanctum, e não a "autenticação nativa do Laravel".
2. **Cálculo em ponto flutuante**: os valores são **armazenados** em tipo exato (`decimal`), mas o **cálculo** usa `float`, com arredondamento a 2 casas por mês. Não afirmar "aritmética exata".
3. **Dashboard**:
   - F soma **todas** as faturas em aberto, de qualquer mês.
   - D soma as parcelas de dívidas **pendentes** com vencimento no mês corrente.
   - Parcelas vencidas de meses anteriores não entram na fórmula; aparecem num alerta separado.
4. **Meses sem taxa lançada não geram juros**: o saldo passa direto para o próximo mês registrado. É uma limitação a registrar no artigo, e o comportamento foi mantido.
5. **Registros de 0,00%**: ao pagar uma parcela num mês sem taxa, o sistema cria o registro do mês com 0,00%, que permanece mesmo se o pagamento for desfeito. Isso é aceito.
6. **Recálculo em cascata**: antes desta etapa, alterar um mês não atualizava os meses seguintes. Foi corrigido no Bloco G (`AmortizacaoService::recalcularAPartirDe`), sem mudar a fórmula.
7. **`GET /dividas/{divida}`**: listado na `SPEC_TESTES.md`, mas **não existe**. A tela da dívida é `/dividas/{divida}/relatorio`; na URI citada só há PUT/DELETE, por isso o GET responde 405. No C6 isso foi aceito como "negado", e nenhum dado de A é exibido.
8. **Versão do Vuexy**: não confirmada no código (o `package.json` do starter-kit indica 3.0.0). Conferir na página da compra.
9. **Taxa de juros**: agora é digitada em **percentual** (ex.: "1,5") e gravada como fração (0,0150). O roteiro T3 pode usar "1,5%".

## 5. Falhas observadas durante o trabalho e causa provável

A execução final não teve falhas. Durante o desenvolvimento ocorreram:

| Ocorrência | Causa | Situação |
|---|---|---|
| 2 falhas (`BrowserSessionsTest` e uma linha do C6) quando a suíte foi chamada de dentro do `tcc:cenarios` | O subprocesso herdou as variáveis do `.env` de **desenvolvimento** (exportadas com `putenv`), que prevaleceram sobre o `.env.testing`: `APP_DEBUG=true`, sessão e banco de desenvolvimento. Com isso o `RefreshDatabase` recriou o banco de desenvolvimento (sem dados importantes). | Corrigido: o comando passa as variáveis do `.env.testing` explicitamente e se recusa a usar banco que não termine em `_test`. Além disso, `tests/TestCase.php` aborta qualquer teste nesse caso. |
| 2 falhas em `RendaLivreRealTest` na primeira versão | Erro do dado de teste: uma parcela de 05/10 já estava vencida na data fixa do teste (15/10) e aparecia no alerta de vencidas. | Dado do teste corrigido. Não era defeito do sistema. |
| 2 falhas em `DashboardControllerTest` | O teste antigo verificava a linha única "soma de todas as dívidas", substituída por uma linha por dívida (Bloco J). | Teste atualizado para o novo formato, mantendo a verificação de isolamento entre usuários. |

## 6. Como reproduzir

```bash
createdb gestao_dividas_test
cp .env.testing.example .env.testing   # ajustar usuário/senha e APP_KEY; DB_DATABASE deve terminar em _test
composer install && yarn && yarn build
php artisan test --testdox
php artisan tcc:cenarios               # gera docs/resultado_cenarios.md e docs/resultado_testes.txt
```
