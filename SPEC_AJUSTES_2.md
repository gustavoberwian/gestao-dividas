# Ajustes de interface e de regras — rodada 2

> Origem: testes manuais do autor no navegador + `RELATORIO_VERIFICACAO.md`.
> Este arquivo **inclui e substitui o item A4** do `SPEC_AJUSTES.md` (idioma).
> Regras gerais: não alterar a fórmula do `AmortizacaoService`; mostrar o diff de cada item;
> rodar `php artisan test` e `yarn build` ao fim de cada bloco; parar se algo quebrar;
> não criar commits sem eu pedir (sugerir as mensagens ao final).

Biblioteca já presente no template e que deve ser **reaproveitada** (nada de dependência nova
sem necessidade): `cleave-zen` (máscaras), `flatpickr` (datas), `notyf` (toasts),
`sweetalert2` (confirmações), `apexcharts`.

---

## Bloco B — Remover a marca do Vuexy

1. **Título da aba:** apenas o título da página (ex.: `Login`, `Dashboard`, `Minhas Dívidas`).
   Sem sufixo e sem nome do template. Ajustar `config/variables.php` (`templateName`,
   `templateDescription`, `templateKeyword`, `creatorName`, `productPage`, `licenseUrl`) e o layout
   que monta o `<title>`.
2. **Favicon:** trocar o do Vuexy por um próprio e simples (SVG com as iniciais "GD"; pode gerar o
   `.ico`/`.png` a partir dele). Remover os ícones de touch do Vuexy.
3. **Logo e nome da aplicação:** "Gestão de Dívidas" no menu lateral e nas telas de login/cadastro.
4. **Meta tags** (description, keywords, og:*, twitter:*): remover ou trocar por texto do projeto.
5. **Rodapé:** exatamente `Feito por Gustavo Berwian`. Remover o link para a Pixinvent.
6. **Links e botões do template:** documentação, "Buy now", GitHub, suporte, licença e qualquer
   texto "Vuexy"/"Pixinvent" visível ao usuário.
7. Rodar `grep -rniE "vuexy|pixinvent" resources app config public --include=*.php --include=*.json --include=*.blade.php`
   e listar o que sobrou. Comentários em arquivos de vendor/CSS/JS do template podem ficar;
   **nada visível ao usuário pode ficar**.

## Bloco C — Idioma e acentuação

1. `APP_LOCALE=pt_BR`, `APP_FALLBACK_LOCALE=pt_BR`, `APP_FAKER_LOCALE=pt_BR`; remover o seletor de
   idioma e `LocaleMiddleware` se não tiver mais função.
2. **Fuso horário:** `APP_TIMEZONE=America/Sao_Paulo` (importa para "vencida" e "fechada").
3. Mensagens de validação, autenticação, senha e paginação em português: criar `lang/pt_BR/*.php`
   (validation, auth, passwords, pagination) com os `attributes` dos campos do sistema
   (renda mensal, valor, taxa de juros, data de vencimento etc.) e `lang/pt_BR.json` para as
   strings do Jetstream/Fortify/Livewire. Se preferir, usar o pacote `laravel-lang/lang`
   (explique a escolha).
4. **Acentuação em toda a interface e nas mensagens de flash dos controllers**: hoje há
   "Divida", "Cartao", "Cartoes de Credito", "mes", "nao", "Evolucao", "Composicao" etc.
   Corrigir para "Dívida", "Cartão", "Cartões de crédito", "mês", "não", "Evolução", "Composição".
5. Formato brasileiro de datas (`dd/mm/aaaa`) e de moeda (`R$ 1.234,56`) em todas as telas.
6. Revisar as telas de login, cadastro, esqueci a senha, redefinir senha, perfil e 2FA do Jetstream:
   listar qualquer texto que ainda esteja em inglês.

## Bloco D — Avisos como toasts

- Trocar os cartões de alerta (`partials.alert-status`) por **toasts** (`notyf`), canto superior
  direito: sucesso some em ~4 s, erro em ~7 s, ambos com botão de fechar e pausa ao passar o mouse.
- Mensagens de `session('status')` e de erro geral viram toast. Erros **de validação de campo**
  continuam inline, em vermelho, abaixo do campo; além disso mostrar um toast curto
  "Verifique os campos destacados".
- Um único componente reutilizável (partial + JS), usado em todas as telas.

## Bloco E — Máscaras e seletor de datas

1. **Moeda** (renda, limite do cartão, valor principal, valor da parcela, valor da fatura):
   máscara `R$ 1.234,56` com `cleave-zen`, campo `type="text" inputmode="decimal"`.
   O servidor continua lendo pelo `App\Support\NumeroBr`; ao enviar, pode mandar o valor
   normalizado (`1234.56`) por campo oculto ou deixar o servidor interpretar.
2. **Percentual** (taxa de juros): máscara `0,00` com sufixo `%`, máximo 100,00.
3. **Datas:** usar `flatpickr` do template, em português (`flatpickr/dist/l10n/pt`), exibindo
   `dd/mm/aaaa` e enviando `Y-m-d` (`altInput`). Para "mês de referência" usar o plugin `monthSelect`
   exibindo `mm/aaaa` e enviando o primeiro dia do mês.
4. Os formulários dentro de modais devem reinicializar as máscaras ao abrir o modal.
5. Teste: o servidor aceita os formatos mascarados (`R$ 1.234,56`, `1.234,56`, `1234,56`, `1234.56`)
   e recusa valores inválidos com mensagem em português.

## Bloco F — Situação derivada (parcelas e faturas)

Estes estados são **calculados na leitura**, não gravados. O banco continua com
`pendente|paga` (parcelas) e `aberta|paga` (faturas). Usar o fuso `America/Sao_Paulo`.

**Parcela** (`Parcela::situacao()`):
- `paga` → status paga
- `vencida` → pendente **e** `data_vencimento` anterior a hoje
- `vence_hoje` → pendente e vencimento = hoje
- `pendente` → demais casos

**Fatura** (`Fatura::situacao()`), com `mes_referencia` = mês do **vencimento**:
- data de vencimento = `dia_vencimento` do cartão no mês de referência (limitar ao último dia do mês);
- data de fechamento = `dia_fechamento` no mesmo mês se `dia_fechamento < dia_vencimento`,
  senão no mês anterior (limitar ao último dia do mês);
- `paga` → status paga
- `vencida` → não paga e hoje > vencimento
- `fechada` → não paga e fechamento < hoje ≤ vencimento (aguardando pagamento)
- `aberta` → não paga e hoje ≤ fechamento (ainda recebendo lançamentos)

Interface: badges de cor (paga = verde, vencida = vermelho, fechada = amarelo, aberta = azul,
vence hoje = laranja) nas listas de parcelas, de faturas e no relatório.
No formulário da fatura, rotular o campo como "Mês de vencimento da fatura".

**Regra do dashboard (não muda):** Renda Livre Real = renda − faturas não pagas (todas, qualquer
situação) − parcelas de dívida **pendentes com vencimento no mês corrente**. Parcelas vencidas de
meses anteriores **não** entram na fórmula; por isso o dashboard deve mostrar um alerta separado
("2 parcelas vencidas somando R$ X") com link para a lista.

## Bloco G — Editar e excluir

Criar, com `Policy` e testes, para: **taxa de juros do mês** (`HistoricoJuros`), **parcela** e
**fatura**. Incluir também **dívida** e **cartão** (editar descrição/credor/limite etc. e excluir),
para o CRUD ficar completo.

Rotas sugeridas:
- `PUT /dividas/{divida}/juros/{historico}`, `DELETE /dividas/{divida}/juros/{historico}`
- `PUT /parcelas/{parcela}`, `DELETE /parcelas/{parcela}`, `PATCH /parcelas/{parcela}/reabrir`
  (desfazer o pagamento)
- `PUT /faturas/{fatura}`, `DELETE /faturas/{fatura}`, `PATCH /faturas/{fatura}/reabrir`
- `PUT|DELETE /dividas/{divida}` e `PUT|DELETE /cartoes/{cartao}`

Interface: botões de editar (modal com o formulário preenchido) e de excluir (confirmação com
`sweetalert2`: "Tem certeza? Esta ação não pode ser desfeita"). Excluir dívida ou cartão avisa
que remove também as parcelas, o histórico e as faturas ligadas.

### Recálculo em cascata (pede decisão minha antes de codar)
Hoje o recálculo só atinge o mês da alteração. Ao editar/excluir, os meses seguintes ficam
desatualizados. **Antes de implementar, escreva em até 15 linhas as regras que você propõe e espere
meu OK.** Proposta de partida:

1. Criar `AmortizacaoService::recalcularAPartirDe(Divida $divida, $mes)`, que reaproveita
   `calcularSaldoDevedor` em ordem para cada mês **que já tenha registro de taxa ou parcela paga**,
   do mês informado até o último. A fórmula não muda.
2. Disparar o recálculo em: editar taxa, excluir taxa, editar parcela (do menor entre mês antigo e
   novo), excluir parcela, marcar paga, desfazer pagamento.
3. **Excluir uma taxa:** se o mês tem parcelas pagas, o registro volta com taxa 0,00% (para não
   "perder" o pagamento do saldo); se não tem, o registro some. Em ambos os casos os meses seguintes
   são recalculados.
4. Valores continuam arredondados a 2 casas a cada mês.
5. Excluir parcela/fatura/dívida usa `DB::transaction`.

Cálculos de aceitação, feitos à mão (usar nos testes): partindo do C1
(10.000,00; taxas 1,5%, 1,2%, 1,0%; pagamentos de 1.000,00 nos 3 meses):
- editar o pagamento do mês 1 para 500,00 → saldos **9.650,00 / 8.765,80 / 7.853,46**;
- excluir a taxa do mês 2 (com pagamento no mês) → mês 2 com 0,00%: saldos **9.150,00 / 8.150,00 / 7.231,50**;
- desfazer o pagamento do mês 3 → mês 3 = **8.342,40**.

## Bloco H — E-mail (recuperação de senha, RF010)

Causa provável de "não recebi": `MAIL_MAILER=log`, que só escreve o e-mail em
`storage/logs/laravel.log`. Confirmar e então:
1. Configurar **Mailpit** para desenvolvimento (captura local, sem enviar de verdade):
   `MAIL_MAILER=smtp`, `MAIL_HOST=127.0.0.1`, `MAIL_PORT=1025`, sem usuário/senha;
   caixa de entrada em `http://localhost:8025`. Documentar no README como instalar/rodar no WSL.
2. `MAIL_FROM_ADDRESS="nao-responder@gestaodividas.local"` e `MAIL_FROM_NAME="Gestão de Dívidas"`.
3. Assunto e corpo do e-mail de redefinição em português (via `lang/pt_BR.json`).
4. Opcional no README: como apontar para um SMTP real (ex.: Gmail com senha de app) **só pelo `.env`**,
   que não vai para o Git. Nunca colocar credenciais no repositório.
5. Teste com `Notification::fake()`: solicitar o link envia a notificação ao usuário certo.

## Bloco I — Navbar e menu de usuário

- No dropdown do usuário: remover o **perfil/role** (texto abaixo do nome) e o **Billing**.
  Remover também itens sem função no sistema (Pricing, FAQ, Settings, etc.). Manter apenas
  "Meu perfil" e "Sair".
- Remover da navbar o que não funciona: busca, atalhos, notificações, seletor de idioma.
  Manter o alternador de tema claro/escuro apenas se funcionar.
- Listar o que foi removido.

## Bloco J — Dashboard mais claro

Diagnóstico do que existe hoje: o gráfico de linha soma todas as dívidas e só tem pontos nos meses
com taxa lançada (com 1 ou 2 pontos parece vazio); a rosca perde o sentido quando a Renda Livre
Real é negativa (a fatia vira 0). Proposta:

1. **Linha superior com 4 cartões:** Renda mensal | Faturas em aberto | Parcelas do mês |
   **Renda Livre Real** (maior, verde/vermelho, e abaixo "X% da renda comprometida").
2. **Faixa de alertas** (some quando não há nada): "N parcelas vencidas", "Fatura do cartão X vence em
   D dias", "Renda mensal não cadastrada" (com botão).
3. **Substituir a rosca por uma barra horizontal empilhada** "Para onde vai sua renda este mês":
   segmentos Faturas | Parcelas | Renda Livre; se passar de 100%, mostrar o excedente em vermelho
   ("comprometido acima da renda").
4. **Lista "Próximos vencimentos" (30 dias)** com parcelas e faturas, ordenadas por data, com badge
   de situação (vencida, vence hoje, em N dias). É mais útil do que um gráfico.
5. **Gráfico de saldo devedor:** uma linha por dívida (com seletor "todas / uma dívida"), ponto
   inicial no valor principal, marcadores, tooltip com taxa e pagamento do mês, subtítulo "como ler"
   em uma frase. Estado vazio com botão ("Cadastrar dívida").
6. **No relatório da dívida (não no dashboard):** colunas empilhadas por mês, "juros do mês" ×
   "amortização" (pagamento − juros). Mostra quanto do que foi pago foi só juros; liga diretamente
   ao tema do projeto.
7. Cores semânticas consistentes (vermelho = dívida/atraso, verde = livre, amarelo = atenção),
   títulos em linguagem simples, valores sempre em `R$ 1.234,56`.

## Bloco K — Testes (somar à Etapa 3 de `SPEC_TESTES.md`)
- Cada nova rota: dono consegue (200/302), outro usuário recebe 403, visitante vai ao login.
- Cascata: os três cálculos de aceitação do Bloco G.
- `Parcela::situacao()` e `Fatura::situacao()` com datas fixas (`Carbon::setTestNow`), cobrindo
  os limites (hoje = vencimento; fechamento maior que vencimento; mês com 30 dias e `dia_fechamento` 31).
- Dashboard: alerta de vencidas e fórmula da Renda Livre Real inalterada.
- Máscaras: o servidor aceita os formatos listados no Bloco E.

## Critério de pronto
- Nenhum texto visível menciona Vuexy/Pixinvent; título da aba = nome da página.
- Toda a interface e as mensagens em português, com acentos.
- Avisos em toast; datas e valores com máscara e seletor de datas do template.
- Parcelas e faturas mostram vencida/fechada/aberta corretamente.
- Editar e excluir funcionam para taxa, parcela, fatura, dívida e cartão, com cascata correta.
- E-mail de redefinição aparece no Mailpit, em português.
- `php artisan test` e `yarn build` passam.
