# 🌟 Site Dinâmico & CMS Dr. George Scapin

Sistema completo e dinâmico desenvolvido em **PHP Puro (Clean Architecture)** e banco de dados **MySQL**, baseado no padrão de referência do projeto `super_app`. Toda a estrutura roda a partir da raiz do projeto, preservando o design e a identidade visual luxuosa da Clínica Scapin.

---

## 💾 Banco de Dados MySQL

- **Database:** `clinica_george`
- **Host:** `127.0.0.1` (Porta `3306`)
- **Tabelas criadas e populadas:**
  - `users` (Administradores do CMS com senha BCRYPT)
  - `procedures` (Tratamentos e procedimentos com fotos e ícones)
  - `posts` (Artigos do blog dinâmico)
  - `custom_pages` (Páginas personalizadas gerenciáveis)
  - `menu_items` (Itens de navegação do menu e rodapé)
  - `hero_content` (Banner e chamadas da Home)
  - `clinic_content` (Textos e citações da página institucional)
  - `contact_messages` (Leads do formulário com status e ação WhatsApp)
  - `newsletter_subscribers` (E-mails capturados no rodapé)
  - `site_settings` (Telefones, endereços, horários e SEO)

Caso precise recriar o banco:
```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS clinica_george CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root clinica_george < database/schema.sql
mysql -u root clinica_george < database/seed.sql
```

---

## 🚀 Como Executar Localmente

Na raiz do projeto:

```bash
php -S 0.0.0.0:8000 -t public
```

Acesse no navegador:
- **Site Institucional Dinâmico:** [http://localhost:8000](http://localhost:8000)
- **Painel Administrativo CMS:** [http://localhost:8000/admin](http://localhost:8000/admin)

---

## 🧪 Suíte de Testes, QA e Auditoria de Segurança

O projeto inclui um ecossistema completo de testes automatizados:

| Comando | Descrição |
|---|---|
| `php scripts/run-all-tests.php` | **Runner Geral**: Executa as 9 baterias de testes em sequência |
| `php scripts/qa-security-audit.php` | **Auditoria de QA & Segurança**: 31 verificações de rotas, headers, CSRF, XSS, rate limit e SEO |
| `php scripts/app-test-suite.php` | **Testes Unitários & Repositórios**: Validação de CRUDs MySQL, Helpers e ImageOptimizer |
| `php scripts/http-e2e-test.php` | **Testes E2E HTTP**: Validação de 41 rotas públicas, formulários, autenticação e exportações CSV |
| `php scripts/deploy-check.php` | **Deploy Check**: Verificação estática de prontidão do servidor de produção |

---

## 📦 Build e Empacotamento de Release

Para compilar, validar e gerar um pacote ZIP limpo e otimizado para deploy em produção:

```bash
php scripts/build-release.php
```

O arquivo gerado em `build/georgescapin_release_*.zip` contém todos os arquivos necessários para deploy, com exclusão automática de arquivos de desenvolvimento, logs, caches e variáveis de ambiente locais.

Para o guia passo a passo completo de produção, consulte [DEPLOYMENT.md](DEPLOYMENT.md).

---

## 🔑 Credenciais do Painel Administrativo

- **E-mail:** `admin@drgeorgescapin.com.br`
- **Senha:** `admin123`

---

## 🏗️ Estrutura do Projeto na Raiz (Clean Architecture)

```
georgescapin/
├── app/
│   ├── Infrastructure/
│   │   ├── Database/Connection.php        ← Conexão PDO Singleton MySQL (utf8mb4)
│   │   ├── Repositories/                  ← Repositórios MySQL (Tratamentos, Posts, Leads, Configs, Menu)
│   │   ├── Security/Auth.php              ← Gerenciador de Sessão e Autenticação BCRYPT
│   │   └── Services/ImageOptimizer.php    ← Otimizador e conversor de imagens para WebP
│   ├── Presentation/
│   │   ├── Controllers/                   ← SiteController, AdminController, AuthController
│   │   ├── Middlewares/                   ← AuthMiddleware (Proteção de rotas do admin)
│   │   └── Views/
│   │       ├── site/                      ← Páginas públicas dinâmicas (Home, Tratamentos, Clínica, Blog, Contato)
│   │       ├── admin/                     ← Telas do CMS (Dashboard, Procedimentos, Posts, Leads, Páginas, SEO)
│   │       └── layouts/                   ← Layouts base (Header, Footer, Admin Sidebar)
│   └── helpers.php                        ← Funções globais e autoloader PSR-4
├── config/
│   ├── app.php                            ← Configurações gerais da clínica
│   ├── database.php                       ← Configuração de conexão MySQL
│   └── routes.php                         ← Mapeamento de rotas amigáveis (GET/POST)
├── database/
│   ├── schema.sql                         ← DDL de tabelas MySQL (InnoDB utf8mb4)
│   └── seed.sql                           ← Carga de dados iniciais do Dr. George Scapin
├── public/
│   ├── index.php                          ← Front Controller com Autoloader PSR-4 e Roteamento
│   ├── .htaccess                          ← Regras de reescrita Apache
│   ├── assets/                            ← CSS, JS, Fonts e Imagens da clínica
│   └── uploads/                           ← Diretório para upload de fotos pelo CMS
├── scripts/
│   ├── build-release.php                  ← Script automatizado de build e empacotamento ZIP
│   ├── run-all-tests.php                  ← Runner unificado das 9 baterias de testes
│   ├── qa-security-audit.php              ← Auditoria especialista em QA, Segurança e Usabilidade
│   ├── app-test-suite.php                 ← Testes unitários e de integração MySQL
│   ├── http-e2e-test.php                  ← Testes E2E HTTP do site e painel
│   └── deploy-check.php                   ← Verificador de prontidão do servidor
├── index.php                              ← Proxy raiz para execução direta
├── .htaccess                              ← Regra raiz de roteamento
├── .env                                   ← Configurações ativas do MySQL (local)
├── .env.example                           ← Template de variáveis de ambiente
├── DEPLOYMENT.md                          ← Guia detalhado de deploy em produção (Apache, Nginx, Hostinger)
└── README.md                              ← Este documento
```

---

## ✨ Telas do Site Público (Dinâmicas)

1. **Início (`/`)**:
   - Hero com chamadas dinâmicas.
   - Seção dos 3 Pilares (Planejamento 360, Cuidado Personalizado, Rejuvenescimento).
   - Grade de Procedimentos dinâmicos com imagens e ícones.
   - Seção de Dúvidas / Queixas frequentes com chamada para agendamento.
   - Newsletter no rodapé com envio Ajax.

2. **George Scapin (`/clinica` ou `/george-scapin`)**:
   - Página biográfica com a foto oficial do Dr. George Scapin, titulação (CRBM 5202), filosofia de atendimento e citação em destaque.

3. **Procedimentos (`/procedimentos` ou `/tratamentos`)**:
   - Layout dividido (*split-screen*) alternado com fotos em alta resolução e descrições detalhadas dos procedimentos (Toxina Botulínica, Prevenção de Rugas, Harmonização Facial, Harmonização Corporal).

4. **Harmonização Facial Full Face (`/harmonizacao-facial`)**:
   - *Landing page* focada no procedimento de Full Face, explicando a metodologia arquitetônica 360° e benefícios.

5. **Blog Dinâmico (`/blog`) e Artigos (`/blog/{slug}`)**:
   - Listagem de artigos educativos e posts completos consumidos diretamente do MySQL com paginação.

6. **Contato (`/contato`)**:
   - Formulário com proteção CSRF, honeypot anti-spam, captcha aritmético dinâmico e persistência de leads no banco.

---

## 🎛️ Telas do Painel Administrativo (CMS)

1. **Dashboard Geral (`/admin`)**:
   - Indicadores em tempo real (Novos contatos, Procedimentos, Artigos no Blog, Inscritos na Newsletter).
   - Tabela de leads recentes com botão de ação rápida para chamar no **WhatsApp**.

2. **Gestão de Procedimentos (`/admin/procedures`)**:
   - CRUD completo com upload de imagens (otimizadas automaticamente em WebP), seletor de ícones Lucide, ordenação e ativação.

3. **Gestão do Blog (`/admin/posts`)**:
   - Publicar novos artigos (`/admin/posts/create`), gerenciar autores, capas e status de publicação.

4. **Central de Leads & Newsletter (`/admin/leads`)**:
   - Gerenciamento de status de contatos (`Novo`, `Atendido`, `Arquivado`).
   - Botões para **Exportar Leads e Assinantes em `.CSV`** para Excel.

5. **Gerenciamento de Páginas & Menus (`/admin/pages` e `/admin/menu`)**:
   - Edição de conteúdos da Home, Clínica, Harmonização e criação de páginas customizadas (`/p/{slug}`).
   - Gerenciamento dos links e botões do menu de navegação.

6. **Conteúdos Institucionais & SEO (`/admin/settings`)**:
   - Edição de telefones, endereço, horários, redes sociais e metatags para o Google.

7. **Meu Perfil & Senha (`/admin/profile`)**:
   - Alteração de dados do administrador e troca de senha segura com BCRYPT.
