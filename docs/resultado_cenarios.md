# Resultado dos cenários C1 a C6

Gerado por `php artisan tcc:cenarios` em 15/10/2026 11:49.
Ambiente: PHP 8.3.6 · Laravel 12.69.2 · PostgreSQL 16.15 (Ubuntu 16.15-0ubuntu0.24.04.1).
Execução sem PHPUnit, em um schema temporário do banco `gestao_dividas_test` (criado e apagado pelo comando).

Fórmula: `SD = SD_anterior × (1 + i) − P`, arredondada a 2 casas a cada mês.
Critério: `|diferença| ≤ 0,01`. C6: esperado acesso negado (403/404) e redirecionamento do visitante ao login.

**Resultado: 23 de 23 verificações OK.**

| Cenário | Esperado | Obtido | Diferença | Status |
|---|---|---|---|---|
| C1 — mês 1 | 9.150,00 | 9.150,00 | 0,00 | OK |
| C1 — mês 2 | 8.259,80 | 8.259,80 | 0,00 | OK |
| C1 — mês 3 | 7.342,40 | 7.342,40 | 0,00 | OK |
| C2 — mês 1 | 5.100,00 | 5.100,00 | 0,00 | OK |
| C2 — mês 2 | 5.202,00 | 5.202,00 | 0,00 | OK |
| C3 | 0,00 | 0,00 | 0,00 | OK |
| C4 | 1.500,00 | 1.500,00 | 0,00 | OK |
| C5 | 3.000,00 | 3.000,00 | 0,00 | OK |
| C5 + bordas (a)–(d) | 3.000,00 | 3.000,00 | 0,00 | OK |
| C6 — B: GET /dividas/6 | negado (rota inexistente: 405) | 405 | — | OK |
| C6 — B: GET /dividas/6/relatorio | negado (403/404) | 403 | — | OK |
| C6 — B: POST /dividas/6/juros | negado (403/404) | 403 | — | OK |
| C6 — B: PATCH /parcelas/11/pagar | negado (403/404) | 403 | — | OK |
| C6 — B: PATCH /faturas/3/pagar | negado (403/404) | 403 | — | OK |
| C6 — B: GET /cartoes/2 | negado (403/404) | 403 | — | OK |
| C6 — B: listagem /dividas | sem dados de A | 200 (sem dados de A) | — | OK |
| C6 — B: listagem /cartoes | sem dados de A | 200 (sem dados de A) | — | OK |
| C6 — B: listagem /dashboard | sem dados de A | 200 (sem dados de A) | — | OK |
| C6 — visitante: GET /dividas/6/relatorio | login (302) | 302 | — | OK |
| C6 — visitante: POST /dividas/6/juros | login (302) | 302 | — | OK |
| C6 — visitante: PATCH /parcelas/11/pagar | login (302) | 302 | — | OK |
| C6 — visitante: PATCH /faturas/3/pagar | login (302) | 302 | — | OK |
| C6 — visitante: GET /cartoes/2 | login (302) | 302 | — | OK |

Observações:
- C5 + bordas (a)–(d): (a) parcela vinculada a fatura; (b) fatura paga; (c) parcela de dívida já paga;
  (d) parcela de dívida com vencimento em outro mês. Nenhum deles pode alterar a Renda Livre Real.
- `GET /dividas/{id}` não existe no sistema (a tela da dívida é `/dividas/{id}/relatorio`); nessa URI só há
  PUT/DELETE, por isso o retorno 405 também é aceito como "negado".
