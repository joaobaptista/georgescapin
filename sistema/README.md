# Nüva — PHP puro + HTML/CSS/JavaScript nativos

Sistema clínico com backend em PHP 8.2+ e interface sem framework: o navegador
carrega HTML estático, CSS e um único módulo JavaScript nativo. Não há Vue,
Vite, Tailwind, Node.js ou etapa de build em produção.

## Estrutura

```text
app/                    Núcleo PHP, controllers e middlewares
database/               Schema SQLite/MySQL, migração e dados de demonstração
public/index.php        Front controller da API e fallback das páginas
public/index.html       Shell da interface nativa
public/assets/css/      Estilos estáticos
public/assets/js/       Router History API, sessão e telas nativas
routes/api.php          27 endpoints REST
scripts/                Smoke test, E2E e verificação de deploy
```

O navegador resolve as telas diretamente — inclusive ao recarregar uma URL como
`/pro/pacientes/prontuario/12` — e o módulo nativo envia `Authorization: Bearer`
em todas as chamadas protegidas.

## Rodar localmente

Instale as dependências PHP e inicie o servidor:

```bash
composer install
php -S 127.0.0.1:8000 -t public
```

Abra [http://127.0.0.1:8000](http://127.0.0.1:8000). Não execute `npm install`,
`npm run dev` ou `npm run build`.

Para preparar o banco configurado em `.env`:

```bash
composer migrate
composer seed
```

## Credenciais de demonstração

| Perfil | E-mail | Senha |
| --- | --- | --- |
| Profissional | `dr.gabriel@nuva.com.br` | `senha123` |
| Paciente | `alegra@example.com` | `senha123` |
| Paciente | `jheniffer@example.com` | `senha123` |
| Paciente | `jefferson@example.com` | `senha123` |

## Testes em banco isolado

Os comandos abaixo usam um SQLite temporário e não alteram o banco da clínica.
O `variables_order=EGPCS` garante que os valores temporários não sejam
substituídos pelo `.env`.

```bash
TEST_DIR="$(mktemp -d /private/tmp/nuva-test.XXXXXX)"
TEST_DB="$TEST_DIR/nuva.sqlite"

DB_CONNECTION=sqlite DB_DATABASE="$TEST_DB" php -d variables_order=EGPCS database/migrate.php
DB_CONNECTION=sqlite DB_DATABASE="$TEST_DB" php -d variables_order=EGPCS database/seed.php

DB_CONNECTION=sqlite DB_DATABASE="$TEST_DB" php -d variables_order=EGPCS -S 127.0.0.1:8000 -t public
```

Em outro terminal:

```bash
NUVA_TEST_DB_PATH="$TEST_DB" php -d variables_order=EGPCS test_api.php
NUVA_TEST_BASE_URL=http://127.0.0.1:8000 php scripts/route-smoke-test.php
NUVA_E2E_BASE_URL=http://127.0.0.1:8000 NUVA_E2E_DB_PATH="$TEST_DB" NUVA_E2E_ALLOW_MUTATIONS=1 php scripts/api-e2e-test.php
php scripts/deploy-check.php
```

- `test_api.php` verifica o núcleo de banco e autenticação.
- `scripts/route-smoke-test.php` testa todas as rotas visuais, CORS, 404/405,
  validação pública e os 23 endpoints protegidos sem token.
- `scripts/api-e2e-test.php` percorre os fluxos autenticados, CRUD, papéis,
  logout, rate limit e proteção de prontuários. Ele aceita apenas localhost e
  exige um SQLite temporário.

## Deploy

Aponte o servidor para `public/`. As regras Apache estão em
`public/.htaccess`; uma compatibilidade para hospedagens que usam a raiz do
projeto está em `.htaccess`. Para Nginx, configuração FastCGI e checklist,
consulte [DEPLOYMENT.md](DEPLOYMENT.md).

## Segurança e permissões

- Todas as consultas usam PDO com prepared statements.
- Tokens Bearer são armazenados com hash no banco e enviados pelo adaptador
  único de `fetch` do frontend.
- Rotas de paciente/profissional usam middleware de papel e controles de posse.
- Prontuários profissionais exigem vínculo por cadastro na clínica ou
  agendamento, impedindo acesso direto a pacientes de outro profissional.
