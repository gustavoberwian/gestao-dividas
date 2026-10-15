# Especificação dos testes — Cenários C1 a C6 e verificação do artigo

> Brief para o Claude Code CLI, na pasta do projeto Laravel (`~/tcc/gestao-dividas`).
> Objetivo: (1) produzir a evidência numérica do Capítulo 5 e (2) conferir se o que o
> Capítulo 4 do artigo afirma bate com o código real. **Não altere regras de negócio para
> "fazer o teste passar":** se um teste falhar, pare e relate o que aconteceu.

## Como aplicar

Cole no CLI:

> "Leia SPEC.md e SPEC_TESTES.md nesta pasta. Primeiro faça somente a PARTE A (verificação
> do artigo), sem alterar nenhum arquivo, e me mostre o relatório. Depois que eu aprovar,
> faça a PARTE B (testes) e, por fim, a PARTE C (relatório de resultados)."

---

## PARTE A — Verificação das afirmações do artigo (somente leitura)

Para cada item, responda **Confirmado / Diferente / Não existe**, citando o arquivo e a linha
que provam. Se for "Diferente", diga o que o código faz de fato.

1. **Autenticação (RF001/RF010):** é autenticação nativa do Laravel, Jetstream, Fortify ou
   Breeze? Existe recuperação de senha por e-mail funcionando?
2. **Policies (RNF002):** existem Policies para `Divida`, `CartaoCredito`, `Fatura` e `Parcela`?
   Estão registradas e chamadas (`authorize`) em todos os métodos de controller que recebem
   um desses models? Liste os controllers/métodos que **não** autorizam.
3. **Listagens:** todas filtram por `auth()->id()` (diretamente ou pela cadeia de relações)?
4. **CHECK em `parcelas`:** a migration cria a constraint "exatamente um de `fatura_id` /
   `divida_id` não nulo"? Mostre o SQL.
5. **Tipos monetários (RNF003):** colunas de valor e taxa são `decimal`/`numeric`? Há algum
   `float` no cálculo (cast, `floatval`, divisão)?
6. **AmortizacaoService (RF007):** a fórmula é `SD = SD_anterior * (1 + i) - P`? Como `P` é
   calculado (parcelas pagas com `data_vencimento` no mês)? Em que ponto há arredondamento?
   O recálculo é disparado ao criar `HistoricoJuros` e ao pagar parcela de dívida?
7. **Dashboard (RF008):** a Renda Livre Real é `renda_mensal − faturas abertas − parcelas
   pendentes de dívidas no mês`? Parcelas ligadas a fatura entram na conta (não deveriam)?
   Faturas pagas entram (não deveriam)? Parcelas de dívida já pagas entram (não deveriam)?
8. **Fluxo das tarefas do Apêndice B:** é possível, só pela interface, (T3) registrar a taxa de
   1,5% e o pagamento de R$ 1.000,00 de uma dívida? Para pagar é preciso antes cadastrar uma
   parcela? Descreva o passo a passo real, tela por tela.
9. **Testes existentes:** o que já existe em `tests/`? `php artisan test` passa hoje?
10. **Ferramentas citadas no artigo:** versões reais de PHP, Laravel, Node, Composer, Yarn,
    PostgreSQL e do Vuexy (`package.json` / `composer.json`).

Termine a Parte A com uma lista de **divergências** e funcionalidades **ausentes** dos
requisitos RF001 a RF011.

---

## PARTE B — Testes automatizados

### Configuração
- PHPUnit com banco PostgreSQL **separado**: `gestao_dividas_test` (configure em `phpunit.xml`
  / `.env.testing`; nunca rode testes contra o banco de desenvolvimento).
- Use `RefreshDatabase`.
- Crie factories (`User`, `CartaoCredito`, `Fatura`, `Divida`, `Parcela`, `HistoricoJuros`) se
  ainda não existirem.
- Comparação de dinheiro: `assertEqualsWithDelta($esperado, (float) $obtido, 0.01)`.
  Para igualdade de string use o valor formatado com 2 casas.
- Datas fixas (use `Carbon::setTestNow`) para o teste não depender do dia em que roda.

### Onde ficam
- `tests/Unit/AmortizacaoServiceTest.php` → C1 a C4
- `tests/Feature/DashboardRendaLivreRealTest.php` → C5
- `tests/Feature/IsolamentoEntreUsuariosTest.php` → C6
- `tests/Feature/IntegridadeBancoTest.php` → CHECK de `parcelas`

Anote em cada teste, em comentário, o código do cenário (C1 a C6) e o requisito (RF/RNF).

### Cenários (valores esperados calculados à mão, independentes do código)

Fórmula: `SD = SD_anterior × (1 + i) − P`, arredondando a 2 casas a cada mês.

| ID | Entrada | Esperado |
|---|---|---|
| C1 | Dívida R$ 10.000,00; pagamento R$ 1.000,00 em cada um dos meses 1 a 3; taxas 1,5%, 1,2% e 1,0% | mês 1: **9.150,00**; mês 2: **8.259,80**; mês 3: **7.342,40** |
| C2 | Dívida R$ 5.000,00; taxa 2,0% nos meses 1 e 2; nenhum pagamento | mês 1: **5.100,00**; mês 2: **5.202,00** |
| C3 | Dívida R$ 1.000,00; taxa 1,5% no mês 1; pagamento R$ 1.015,00 | mês 1: **0,00** |
| C4 | Dívida R$ 2.000,00; taxa 0,0% no mês 1; pagamento R$ 500,00 | mês 1: **1.500,00** |
| C5 | Receita R$ 5.000,00; fatura aberta R$ 1.200,00; parcelas pendentes de dívida no mês somando R$ 800,00 | Renda Livre Real **3.000,00** |
| C6 | Usuário B tenta acessar dados do usuário A | acesso negado, nenhum dado de A exibido |

**C1 a C4** — chame `AmortizacaoService::calcularSaldoDevedor` mês a mês, em ordem. Crie as
parcelas pagas com `data_vencimento` dentro de cada mês. Verifique também que
`HistoricoJuros.saldo_devedor_calculado` e `Divida.saldo_devedor_atual` (cache) foram
persistidos com o mesmo valor.

**C5** — faça requisição autenticada a `GET /dashboard` e verifique o valor exibido/retornado.
Acrescente, no mesmo arquivo, casos de borda que **não podem** alterar o resultado:
(a) uma parcela vinculada a **fatura** (não entra em D, evita dupla contagem);
(b) uma fatura com status `paga`;
(c) uma parcela de dívida com status `paga`;
(d) uma parcela de dívida com vencimento em outro mês.
Se algum desses entrar na conta, isso é um **defeito**: relate, não esconda.

**C6** — crie usuários A e B, com dívida, cartão, fatura e parcela de A. Autenticado como B,
verifique que cada rota abaixo responde **403 ou 404** e que listagens de B **não contêm**
nenhum registro de A:
- `GET /dividas/{divida}`, `GET /dividas/{divida}/relatorio`, `POST /dividas/{divida}/juros`
- `PATCH /parcelas/{parcela}/pagar`, `PATCH /faturas/{fatura}/pagar`
- `GET /dividas`, `GET /dashboard` (valores de B não incluem os de A)
Como visitante (não autenticado), as mesmas rotas devem redirecionar para o login.

**CHECK de `parcelas`** — inserir parcela com `fatura_id` **e** `divida_id` ⇒ falha do banco;
com nenhum dos dois ⇒ falha do banco; com exatamente um ⇒ sucesso.

---

## PARTE C — Relatório de resultados (evidência para o Capítulo 5)

1. Rode `php artisan test --testdox` e salve a saída em `docs/resultado_testes.txt`.
2. Crie o comando `php artisan tcc:cenarios` que executa C1 a C5 **sem** PHPUnit, sobre um
   banco temporário, e imprime uma tabela:

   `Cenário | Esperado | Obtido | Diferença | Status (|diferença| ≤ 0,01)`

   e a salva em `docs/resultado_cenarios.md`. Para C6 imprima `Esperado: negado | Obtido: <HTTP>`.
3. Em `docs/relatorio_tcc.md`, liste: resultado da Parte A (divergências), total de testes,
   quantos passaram, e qualquer teste que falhou **com a causa provável**.

## Regras

- Não edite `SPEC.md` nem as regras de negócio já implementadas sem me perguntar.
- Se achar um defeito, **corrija só depois de me mostrar** o diff proposto: o defeito e a
  correção viram conteúdo do artigo (seção de resultados), então preciso saber.
- Mostre a estrutura criada antes de rodar a suíte completa.
