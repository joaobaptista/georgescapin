# Nüva — PHP puro + HTML/CSS/JavaScript nativos

Sistema clínico com backend em PHP 8.2+ e interface sem framework: o navegador
carrega HTML estático, CSS e um único módulo JavaScript nativo. Não há Vue,
Vite, Tailwind, Node.js ou etapa de build em produção.

## Estrutura

```text
app/Domain/             Regras de negócio puras (DDD): agenda, clínico, catálogo, identidade
app/Application/        Casos de uso e portas (interfaces) que eles exigem
app/Infrastructure/     Adaptadores: PDO, bcrypt, relógio, configuração, container
app/Http/               Controllers finos, middlewares, Router, Kernel, validação
bootstrap/app.php       Raiz de composição: liga cada porta à sua implementação
database/               Schema SQLite/MySQL, migração e dados de demonstração
tests/                  Testes unitários de domínio e casos de uso (sem banco)
public/index.php        Front controller da API e fallback das páginas
public/index.html       Shell da interface nativa
public/assets/css/      Estilos estáticos
public/assets/js/       Router History API, sessão e telas nativas
routes/api.php          28 endpoints REST
scripts/                Smoke test, E2E e verificação de deploy
```

A organização em camadas e as regras de cada contexto estão em
[ARQUITETURA.md](ARQUITETURA.md).

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

> ⚠️ O seed **apaga todas as tabelas** antes de inserir os dados de
> demonstração. Ele nunca roda com `APP_ENV=production` e, se o banco já tiver
> usuários, só continua com `composer seed -- --force`.

Agendamentos aceitam `AAAA-MM-DD HH:MM[:SS]` (também com `T`) e são gravados
como `AAAA-MM-DD HH:MM:SS`. Pacientes só agendam horários futuros, comparados no
fuso da clínica: `APP_TIMEZONE` (padrão `America/Sao_Paulo`). O procedimento
informado precisa pertencer ao profissional do agendamento.

Dois atendimentos não cancelados do mesmo profissional não podem se sobrepor
(HTTP 409). A duração é a do procedimento, ou 30 minutos sem procedimento;
atendimentos encostados (um termina quando o outro começa) são permitidos.

> ⚠️ **Ao atualizar um servidor existente, rode `composer migrate`.** A migração
> cria a coluna `users.activated_at`; sem ela, cadastros e ativações falham.

## Ativação de conta e recuperação de senha

O sistema não envia e-mails nem mensagens. Os links são gerados e entregues
pela clínica (WhatsApp, presencialmente), valem por 72 horas, funcionam uma
única vez e encerram as sessões abertas da conta. O banco guarda só o hash.

- **Paciente cadastrado pelo profissional:** a conta nasce pendente. Na tela do
  prontuário, em *Acesso ao portal*, o profissional que fez o cadastro gera o
  link de ativação (informando o e-mail do paciente, se faltar). Contas já
  ativas não aceitam esse link, para que um profissional não assuma a conta do
  paciente.
- **Senha esquecida (paciente ou profissional):** a administração, no
  servidor, depois de confirmar a identidade da pessoa:

  ```bash
  php scripts/password-reset-link.php --email=pessoa@exemplo.com --base-url=https://sistema.clinica.com.br
  ```

## Cadastrar profissionais

O cadastro público do site cria apenas contas de paciente. Contas de
profissional dão acesso a dados clínicos e são criadas pela clínica:

```bash
NUVA_PRO_PASSWORD='senha-inicial' php scripts/create-professional.php \
  --name="Dra. Ana Souza" --email=ana@clinica.com.br \
  --specialty="Dermatologia" --crm="CRM/SP 000000" --city="São Paulo" --state=SP
```

Sem `NUVA_PRO_PASSWORD`, uma senha aleatória é gerada e exibida uma única vez.

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
composer test   # unitários: domínio, casos de uso e regras de arquitetura (sem banco)
NUVA_TEST_DB_PATH="$TEST_DB" php -d variables_order=EGPCS test_api.php
NUVA_TEST_BASE_URL=http://127.0.0.1:8000 php scripts/route-smoke-test.php
NUVA_E2E_BASE_URL=http://127.0.0.1:8000 NUVA_E2E_DB_PATH="$TEST_DB" NUVA_E2E_ALLOW_MUTATIONS=1 php scripts/api-e2e-test.php
php scripts/deploy-check.php

# Interface (opcional: requer Node 22+ e Google Chrome, sem npm install)
NUVA_UI_BASE_URL=http://127.0.0.1:8000 NUVA_UI_ALLOW_MUTATIONS=1 node scripts/ui-e2e-test.mjs --shots="$TEST_DIR/telas"
```

- `tests/run.php` (`composer test`) cobre as regras de domínio e os casos de uso
  com repositórios em memória, e falha se uma camada interna depender de uma
  externa.
- `test_api.php` verifica a infraestrutura contra o banco: sessões (expiração
  absoluta e por inatividade), links de senha, limite de tentativas e fuso.
- `scripts/route-smoke-test.php` testa todas as rotas visuais, CORS, 404/405,
  validação pública e os 24 endpoints protegidos sem token.
- `scripts/api-e2e-test.php` percorre os fluxos autenticados, CRUD, papéis,
  logout, rate limit e proteção de prontuários. Ele aceita apenas localhost e
  exige um SQLite temporário.
- `scripts/ui-e2e-test.mjs` abre um Chrome headless e usa o sistema como o
  médico e o paciente usariam: agenda, status, conflitos, pacientes,
  prontuário, ativação, procedimentos, configurações, tema e telas de celular
  (incluindo controles cortados). Falha em erro de JavaScript ou violação de
  CSP. Aceita apenas localhost, pode ser repetido no mesmo banco e restaura as
  contas de demonstração ao final.

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
  O profissional só agenda pacientes já vinculados a ele; um vínculo novo por
  agendamento nasce de uma solicitação do próprio paciente. Listagem e busca de
  pacientes seguem a mesma regra (`App\Core\ClinicalAccess`).
- Exceções não tratadas retornam JSON 500 genérico e são registradas no log do
  servidor. `APP_DEBUG=true` inclui a mensagem do erro na resposta — use apenas
  em desenvolvimento local.
