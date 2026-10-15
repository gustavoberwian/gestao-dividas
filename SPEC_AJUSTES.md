# Ajustes antes da coleta — brief para o Claude Code CLI

> Origem: `RELATORIO_VERIFICACAO.md`. Mostre o diff de cada item e rode `php artisan test` ao
> final. Não mude a fórmula nem as regras do `AmortizacaoService`.

## A1 — Tela para cadastrar a renda mensal (RF002) — **obrigatório**
- Criar um formulário que envie `PUT /perfil/renda` (já existe rota e controller).
- Colocar no Dashboard (cartão "Renda mensal" com botão "Editar") ou em página própria, com link no
  menu lateral (`resources/menu/verticalMenu.json`).
- Mostrar a mensagem de status ao salvar. Campo numérico com 2 casas, aceitando vírgula.
- Teste de feature: o formulário aparece na página e salvar atualiza `users.renda_mensal`.

## A2 — Padronizar o termo "Renda Livre Real" — **obrigatório**
- No dashboard, trocar o rótulo "Saldo disponivel" por "Renda Livre Real", com uma dica curta
  (tooltip ou texto pequeno): "Renda mensal − faturas em aberto − parcelas de dívidas do mês".
- Atualizar o teste do dashboard se ele depender do texto antigo.

## A3 — Taxa de juros em percentual — **obrigatório**
- Em `relatorio.blade.php` o campo deve receber **percentual** (ex.: `1,5`), com rótulo
  "Taxa de juros do mês (%)".
- No `HistoricoJurosController`, converter para fração (`/ 100`) antes de gravar; validar
  `between:0,100`. A coluna continua em fração (decimal(6,4)); a tela de relatório já exibe `×100`.
- Aceitar vírgula decimal. Atualizar os testes (`HistoricoJurosControllerTest`) para enviar percentual.

## A4 — Idioma — **recomendado**
- `APP_LOCALE=pt_BR` (e `fallback_locale`), traduzindo ou conferindo os textos das telas de login,
  registro, recuperação de senha e perfil do Jetstream. Se algo ficar em inglês, liste.

## A5 — Dados de demonstração para o teste de uso — **recomendado**
- Um seeder opcional (`php artisan db:seed --class=DemoSeeder`) que cria 1 usuário de demonstração
  (e-mail e senha fictícios, não reais) sem dívidas, para o pesquisador mostrar o fluxo. Não alterar
  o `DatabaseSeeder` existente.

## A6 — README — **recomendado**
- Substituir o README padrão do Laravel por um curto: descrição, stack (Laravel 12, Jetstream/Livewire,
  PostgreSQL, Vuexy), como rodar e como testar.

## A7 — Testes para o artigo (evidência) — **depois dos ajustes**
- Executar a `SPEC_TESTES.md` (Partes B e C): arquivos `CenariosC1C4Test`, `RendaLivreRealTest`,
  `IsolamentoEntreUsuariosTest` e `IntegridadeBancoTest`, mais o comando `php artisan tcc:cenarios`
  que gera `docs/resultado_cenarios.md`.

## Critério de pronto
- Um usuário novo consegue, **só pela interface**: cadastrar a renda (T1), cadastrar a dívida (T2),
  cadastrar parcela, marcá-la como paga e informar a taxa de 1,5% digitando "1,5" (T3), ver o saldo
  e o relatório (T4), cadastrar cartão e fatura (T5) e ver a "Renda Livre Real" no dashboard (T6).
- `php artisan test` passa.
