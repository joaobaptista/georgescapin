# 📋 Roadmap & Planejamento: Migração para Site Dinâmico e CMS (Dr. George Scapin)

Documento de referência para evolução técnica, correções pendentes e arquitetura do novo sistema dinâmico em **PHP Puro** com **Painel Administrativo (CMS)**, baseado no padrão arquitetural do projeto `super_app`.

---

## 1. 🔍 Diagnóstico e Backlog do Site Atual (Estático)

### 🔴 Problemas Críticos Identificados
1. **Formulário de Contato Sem `name` nos Inputs**:
   - Arquivo: `contato.html` (linhas 59, 64, 69).
   - *Impacto:* Ao submeter o formulário, o `FormData` não captura os valores dos campos `nome`, `telefone` e `mensagem`, enviando payload vazio para o backend.
2. **CORS Excessivamente Aberto (`*`) no Backend**:
   - Arquivo: `backend/api.php` (linha 3).
   - *Impacto:* Expõe os endpoints de disparo para requisições não autorizadas de qualquer origem.
3. **Credenciais de Banco de Dados Hardcoded**:
   - Arquivo: `backend/api.php` (linhas 7-10).
   - *Impacto:* Risco de segurança e impossibilidade de troca fácil de ambiente (dev/prod).

### 🟡 Melhorias Técnicas e UX
1. **Unificação de Scripts & Eliminação de Código Duplicado**:
   - A lógica de alternância de tema (Dark/Light), menu mobile e scroll do header está duplicada em 4 arquivos HTML (`index.html`, `tratamentos.html`, `clinica.html`, `contato.html`).
2. **Componentização de Header, Footer e Meta Tags**:
   - Atualmente qualquer alteração em links, telefone ou endereço exige edição manual em 4 arquivos.
3. **Feedback Visual sem `alert()`**:
   - A newsletter e o contato usam popups `alert()` nativos do navegador. Substituir por notificações modernas tipo Toast integradas ao design do site.
4. **Variável CSS Ausente**:
   - `--card-bg` em `style.css` (linha 629) não declarada no `:root`.
5. **Otimização de Mídia & SEO**:
   - Adição de `<link rel="icon">` (Favicon).
   - Conversão de PNGs (~700KB cada) para WebP com carregamento lazy e srcset.
   - Inclusão de botão flutuante de WhatsApp com mensagem pré-definida.

---

## 2. 🏛️ Arquitetura do Novo Site Dinâmico & CMS

Seguindo o padrão de **Arquitetura Limpa em Camadas (Pure PHP)** de referência (`super_app`):

```
site_dinamico/
├── app/
│   ├── Domain/                      # Regras de Negócio, Entidades e Interfaces
│   │   ├── Entities/                # Procedure, User, Lead, Banner, ClinicInfo, Subscriber
│   │   └── Repositories/            # Interfaces de Repositório
│   ├── Application/                 # Casos de Uso (UseCases) e DTOs
│   │   ├── UseCases/
│   │   │   ├── Auth/                # Login, Logout, Alteração de Senha
│   │   │   ├── Procedures/          # Listar, Criar, Editar, Excluir tratamentos
│   │   │   ├── Leads/               # Listar contatos, marcar como atendido, exportar
│   │   │   ├── Newsletter/          # Inscrição, listagem de inscritos
│   │   │   ├── Content/             # Edição de textos da Clínica, Hero e SEO
│   │   │   └── Upload/              # Processamento e otimização de imagens (WebP)
│   │   └── Services/                # Serviços de suporte (AuthService, MailService)
│   ├── Infrastructure/              # Implementações concretas
│   │   ├── Database/                # Conexão PDO Singleton (MySQL nativo)
│   │   ├── Repositories/            # Repositórios PDO concretos (MySQL)
│   │   └── Security/                # Hash de senhas, CSRF Token, Sessão e Middleware
│   ├── Presentation/                # Camada de Apresentação
│   │   ├── Controllers/             # SiteController, AdminController, AuthController, etc.
│   │   ├── Middlewares/             # AuthMiddleware, CsrfMiddleware
│   │   └── Views/                   # Templates PHP puros
│   │       ├── site/                # Páginas públicas (Home, Tratamentos, Clínica, Contato)
│   │       ├── admin/               # Painel CMS (Dashboard, Tratamentos, Leads, Configurações)
│   │       └── layouts/             # Header, Footer, Admin Sidebar, Toast
│   └── helpers.php                  # Funções auxiliares (asset(), url(), csrf_token(), sanitize())
├── config/
│   ├── app.php                      # Configurações gerais (nome, url, tema padrão, timezone)
│   ├── database.php                 # Parâmetros de conexão MySQL (127.0.0.1:3306 / clinica_george)
│   └── routes.php                   # Mapeamento de rotas GET e POST
├── database/
│   ├── schema.sql                   # Estrutura de tabelas DDL (MySQL InnoDB utf8mb4)
│   └── seed.sql                     # Dados iniciais já extraídos do site estático
├── public/                          # DocumentRoot exposto pelo servidor web
│   ├── index.php                    # Front Controller com roteamento e Autoloader PSR-4
│   ├── .htaccess                    # Regras de reescrita Apache (mod_rewrite)
│   ├── assets/                      # CSS, JS, Fonts, Imagens estruturais
│   └── uploads/                     # Imagens enviadas dinamicamente pelo CMS
├── .env.example                     # Modelo de variáveis de ambiente
└── README.md                        # Guia de instalação e execução
```

---

## 3. 💾 Modelagem do Banco de Dados (Schema SQL)

### Tabelas Principais:
1. **`users`**: Administradores do sistema (nome, e-mail, senha hashed com `PASSWORD_BCRYPT`, status).
2. **`procedures`**: Tratamentos estéticos (título, subtítulo, descrição completa, ícone lucide, imagem, ordem de exibição, ativo/inativo, slug).
3. **`clinic_info`**: Informações institucionais (missão, valores, citação destacada, endereço, telefone, horários, script de mapa, meta SEO).
4. **`hero_banners`**: Conteúdo da seção Hero principal (título, subtítulo, texto do botão, imagem de fundo dark/light).
5. **`contact_messages`**: Leads e mensagens enviadas pelo formulário (nome, telefone, mensagem, status 'novo'/'lido'/'respondido', data).
6. **`newsletter_subscribers`**: E-mails cadastrados para novidades (e-mail, data, status).
7. **`site_settings`**: Configurações chave-valor (meta tags, pixels de rastreamento, links sociais).

---

## 4. 🎛️ Módulos do Painel Administrativo (CMS)

1. **Dashboard Geral**:
   - Métricas: Total de contatos no mês, novos inscritos na newsletter, tratamentos ativos.
   - Tabela de leads recentes com ação rápida para chamar no WhatsApp.
2. **Gestor de Tratamentos (Procedimentos)**:
   - CRUD completo: criar novo tratamento, editar imagem, escolher ícone, ordenar cards.
3. **Gestor de Conteúdo Institucional**:
   - Edição dos textos da Home, página "A Clínica", citação em destaque e horários de funcionamento.
4. **Central de Leads & Contatos**:
   - Visualização de mensagens com filtros, histórico e exportação.
5. **Inscritos na Newsletter**:
   - Listagem e exportação em CSV para campanhas de e-mail marketing.
6. **Configurações Gerais & SEO**:
   - Edição de títulos, meta descriptions, telefone/WhatsApp de atendimento e códigos de analytics.

---

## 5. 🚀 Roteiro de Implementação em Fases

- [x] **Fase 0**: Diagnóstico do site estático e criação deste roadmap.
- [x] **Fase 1**: Criação da estrutura base do `site_dinamico` com padrão Clean Architecture (`super_app`).
- [x] **Fase 2**: Implementação do Front Controller, Roteamento, Helpers e Database Connection.
- [x] **Fase 3**: Criação do Schema SQL e Seed com o conteúdo atual da Clínica Dr. George Scapin.
- [x] **Fase 4**: Migração das views públicas (Home, Tratamentos, Clínica, Contato) consumindo dados do banco.
- [x] **Fase 5**: Criação do módulo de Autenticação e do Painel CMS (Admin) para gestão completa.
