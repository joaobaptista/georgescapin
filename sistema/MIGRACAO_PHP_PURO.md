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
sessão em respostas `401`. A API mantém os controles no servidor: além de
papel de usuário, prontuários e dados detalhados de pacientes só são liberados
ao profissional responsável ou a quem tenha um agendamento com o paciente.

## Operação

As instruções atuais de banco, testes e publicação estão em [README.md](README.md)
e [DEPLOYMENT.md](DEPLOYMENT.md).
