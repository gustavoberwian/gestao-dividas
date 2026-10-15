# Gestão de Dívidas

Sistema web para controle de dívidas e cartões de crédito, desenvolvido como Trabalho de
Conclusão de Curso. Calcula o saldo devedor mês a mês (`SD = SD_anterior × (1 + i) − P`) e
mostra a **Renda Livre Real** (renda mensal − faturas em aberto − parcelas de dívidas do mês).

## Stack

- PHP 8.2+ e Laravel 12
- Laravel Jetstream (stack Livewire, sem Teams), com Fortify e Sanctum, para a autenticação
- PostgreSQL 16
- Template Vuexy (Bootstrap 5) com Vite; dependências front-end via Yarn
- PHPUnit para os testes

## Como rodar

Requisitos: PHP ≥ 8.2, Composer ≥ 2.2, Node ≥ 18.12, Yarn e PostgreSQL.

```bash
composer install
yarn install
cp .env.example .env          # ajuste DB_USERNAME / DB_PASSWORD
php artisan key:generate
createdb gestao_dividas
php artisan migrate
yarn build
php artisan serve             # http://localhost:8000
```

Usuário de demonstração (opcional, recriado do zero a cada execução):

```bash
php artisan db:seed --class=DemoSeeder   # demo@gestaodividas.local / demo12345
```

## E-mail em desenvolvimento (Mailpit)

A recuperação de senha envia e-mail. Em desenvolvimento, o [Mailpit](https://mailpit.axllent.org/)
captura as mensagens localmente, sem enviar de verdade. O `.env.example` já aponta para ele
(`MAIL_MAILER=smtp`, `MAIL_HOST=127.0.0.1`, `MAIL_PORT=1025`, sem usuário/senha).

No WSL, escolha uma das opções:

```bash
# com Docker
docker run -d --name mailpit --restart unless-stopped -p 1025:1025 -p 8025:8025 axllent/mailpit

# ou binário (script oficial de instalação), depois rode `mailpit` em um terminal
sudo bash < <(curl -sL https://raw.githubusercontent.com/axllent/mailpit/develop/install.sh)
```

Caixa de entrada: http://localhost:8025. Sem o Mailpit rodando, o envio falha; para só gravar o
e-mail em `storage/logs/laravel.log`, use `MAIL_MAILER=log`.

Para usar um SMTP real (ex.: Gmail com [senha de app](https://myaccount.google.com/apppasswords)),
altere **apenas o `.env`**, que não vai para o Git:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_SCHEME=null
MAIL_USERNAME=seu.email@gmail.com
MAIL_PASSWORD="senha-de-app-de-16-caracteres"
MAIL_FROM_ADDRESS=seu.email@gmail.com
```

Nunca coloque credenciais no `.env.example` nem em outro arquivo versionado.

## Como testar

Os testes usam um banco **separado** (`gestao_dividas_test`), configurado em `.env.testing`.

```bash
createdb gestao_dividas_test
cp .env.testing.example .env.testing    # ajuste usuário/senha e APP_KEY
yarn build                               # as views precisam do manifest do Vite
php artisan test
```

O `DB_DATABASE` do `.env.testing` precisa terminar em `_test`: os testes recriam as tabelas e
abortam se o banco não for de testes.

Evidência dos cenários do TCC (C1 a C6), sem PHPUnit, em um schema temporário do banco de testes:

```bash
php artisan tcc:cenarios   # gera docs/resultado_cenarios.md e docs/resultado_testes.txt
```

Síntese dos resultados em `docs/relatorio_tcc.md`.
