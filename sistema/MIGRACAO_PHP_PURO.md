# Arquitetura atual: PHP puro e frontend nativo

O projeto foi consolidado como uma aplicação sem framework no backend e no
frontend.

- **Backend:** PHP 8.2+, PDO, roteador próprio, controllers e middlewares.
- **Frontend:** `public/index.html`, `public/assets/css/app.css` e
  `public/assets/js/app.js`.
- **Navegação:** History API no navegador; o servidor entrega o shell estático
  para URLs visuais e encaminha apenas `/api` e `/api/*` ao roteador PHP.
- **Dados:** SQLite ou MySQL, configurados por `.env`.
- **Dependências:** Composer fornece somente o autoload e `vlucas/phpdotenv`.

## Fluxo de rota

```text
URL visual → public/index.php → public/index.html → app.js → tela nativa
/api/*     → public/index.php → routes/api.php  → controller PHP → JSON
```

O router PHP diferencia API inexistente (`404`) de método inválido (`405` com
header `Allow`). URLs como `/apiary` permanecem rotas visuais, não são tratadas
como API.

## O que foi removido

Não há runtime Laravel ou pacote `laravel/*` / `illuminate/*` no Composer. Os
artefatos visuais do projeto anterior também foram removidos:

- arquivos Blade e o template Swagger legado;
- código-fonte e bundle do frontend baseado em componentes;
- configuração e dependências de build do frontend;
- regras de ignore específicas desses runtimes.

Os assets que permanecem em `public/assets/` são estáticos e já prontos para o
servidor web. Não existe etapa de compilação para publicar uma alteração em
HTML, CSS ou JavaScript.

## Autorização clínica

O JavaScript centraliza as chamadas `fetch`, injeta o token Bearer e limpa a
sessão em respostas `401`. A API mantém os controles no servidor
(`App\Core\ClinicalAccess`):

- Um profissional só vê, lista, busca ou abre o prontuário de pacientes
  vinculados a ele: cadastrados por ele ou com agendamento com ele.
- O vínculo por agendamento só nasce de uma solicitação do próprio paciente.
  O profissional não consegue agendar um paciente sem vínculo, o que impede
  criar um agendamento falso para ganhar acesso a um prontuário.
- O cadastro público cria apenas pacientes; profissionais são criados pela
  clínica (`scripts/create-professional.php`).
- O link de ativação de conta só pode ser gerado pelo profissional que
  cadastrou o paciente e só para contas nunca ativadas, para que ninguém
  assuma a conta de um paciente já ativo.

## Operação

As instruções atuais de banco, testes e publicação estão em [README.md](README.md)
e [DEPLOYMENT.md](DEPLOYMENT.md).
