# Arquitetura do backend

O backend segue **Arquitetura Limpa** com modelagem **DDD**. As dependências
apontam sempre para dentro: o domínio não conhece banco, HTTP nem framework.
`tests/Unit/ArchitectureTest.php` falha se essa regra for quebrada.

```text
            ┌──────────────────────────────────────────────┐
  HTTP  ──► │ app/Http           controllers, middlewares   │
            │   ┌──────────────────────────────────────┐   │
            │   │ app/Application  casos de uso, portas │   │
            │   │   ┌──────────────────────────────┐   │   │
            │   │   │ app/Domain  entidades, regras │   │   │
            │   │   └──────────────────────────────┘   │   │
            │   └──────────────────────────────────────┘   │
  PDO   ◄── │ app/Infrastructure  repositórios, adaptadores │
            └──────────────────────────────────────────────┘
               bootstrap/app.php liga portas a adaptadores
```

| Camada | Responsabilidade | Pode depender de |
| --- | --- | --- |
| `Domain` | Entidades, objetos de valor, serviços de domínio, interfaces de repositório e erros de negócio | nada externo |
| `Application` | Um caso de uso por ação; portas como `Transaction`, `RateLimiter`; consultas de leitura | `Domain` |
| `Infrastructure` | Implementações PDO, bcrypt, relógio, configuração, container de injeção | `Domain`, `Application` |
| `Http` | Validação de formato, conversão de erros em status HTTP, autenticação, roteamento | todas |

## Contextos do domínio

- **Scheduling (agenda)** — `Appointment` é a raiz de agregado. O ciclo de vida
  fica em `AppointmentStatus`:

  ```text
  agendado ──► confirmado ──► concluído (final: histórico clínico)
     │              │
     └──────► cancelado ◄─────┘ ──► agendado (reativação pelo profissional)
  ```

  O paciente só cancela (atendimentos em aberto) e nunca exclui. O profissional
  não exclui atendimentos concluídos. `Agenda` garante um atendimento por vez
  por profissional (`TimeSlot`, duração do procedimento ou 30 min).
- **Clinical (clínico)** — `Patient`, `MedicalRecord` (somente inclusão) e
  `ClinicalAccess`: o profissional só vê pacientes vinculados (cadastrados por
  ele ou com agendamento com ele), e o vínculo por agendamento nasce de uma
  solicitação do paciente.
- **Catalog (catálogo)** — `Procedure`: cada profissional gerencia o próprio.
- **Identity (identidade)** — `User`, `Session` (12 h de validade, 2 h de
  inatividade), links de senha de uso único e login com o Google
  (`StartGoogleSignIn` / `CompleteGoogleSignIn`: OpenID Connect com PKCE,
  `state` preso a cookie HttpOnly, `nonce`, e-mail verificado; o navegador
  recebe só um código de uso único, trocado por sessão via POST).
- **Audit (auditoria)** — `AuditTrail`: quem leu ou alterou dados sensíveis.

## Leitura e escrita

Escritas passam pelos agregados e repositórios do domínio. Leituras para telas
usam as interfaces `*Queries` da camada de aplicação, implementadas em
`Infrastructure/Persistence/Queries`, que já devolvem o formato da API. Isso
evita carregar agregados só para exibir listas.

## Erros

Regras de negócio lançam subclasses de `Domain\Shared\DomainError`. O
`Http\ErrorResponder` é o único lugar que as converte em HTTP:

| Erro | Status |
| --- | --- |
| `ValidationFailed` (formato da entrada) e `RuleViolation` | 422 |
| `NotFound` | 404 |
| `AccessDenied` | 403 |
| `Conflict` (ex.: horário ocupado) | 409 |
| `InvalidSecret` (senha atual ou token incorretos) | 400 |
| `Unauthenticated` | 401 |
| `TooManyAttempts` (com `Retry-After`) | 429 |
| qualquer outra exceção | 500 genérico; detalhes só no log |

## Tempo

Todas as datas vêm do `Clock`, no fuso da clínica (`APP_TIMEZONE`, padrão
`America/Sao_Paulo`). Nenhum SQL usa `NOW()` ou `datetime('now')`.

## Adicionar uma funcionalidade

1. Regra nova: no agregado ou serviço de domínio, com teste em `tests/Unit`.
2. Caso de uso em `app/Application/<Contexto>`, recebendo `Actor` e dados já
   validados no formato.
3. Se precisar de persistência nova: interface no domínio (ou `*Queries` na
   aplicação), implementação PDO na infraestrutura e ligação em
   `bootstrap/app.php`.
4. Controller fino em `app/Http/Controllers` e rota em `routes/api.php`.

## Auditoria

```bash
php scripts/audit-log.php --patient=12   # quem acessou o prontuário do paciente 12
php scripts/audit-log.php --user=5       # o que o usuário 5 fez
```

Eventos: `login.succeeded`, `login.failed`, `login.succeeded_google`,
`account.registered_google`, `account.google_linked`, `patient.viewed`,
`medical_records.viewed`, `medical_record.created`,
`patient.activation_link_issued`, `appointment.deleted`,
`account.password_changed`, `account.email_changed`, `account.password_set`.
