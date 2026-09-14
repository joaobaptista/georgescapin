# 🌟 Site Dinâmico & CMS Dr. George Scapin

Sistema completo e dinâmico desenvolvido em **PHP Puro (Clean Architecture)** e banco de dados **MySQL**, baseado no padrão de referência do projeto `super_app`. Toda a estrutura roda a partir da raiz do projeto, preservando o design e a identidade visual luxuosa da Clínica Scapin.

---

## 💾 Banco de Dados MySQL

- **Database:** `clinica_george`
- **Host:** `127.0.0.1` (Porta `3306`)
- **Tabelas criadas e populadas:**
  - `users` (Administradores do CMS com senha BCRYPT)
  - `procedures` (Tratamentos e procedimentos com fotos e ícones)
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

## 🚀 Como Executar o Projeto

Na raiz do projeto (`site_george/`):

```bash
php -S localhost:8000 -t public public/index.php
```

Para produção, use o guia em [DEPLOYMENT.md](DEPLOYMENT.md). O diretório público recomendado é `public/`; a aplicação também possui uma regra de compatibilidade para hosts Apache que forçam a raiz do projeto como DocumentRoot.

Antes de liberar em uma hospedagem com acesso a terminal, valide o ambiente com:

```bash
php scripts/deploy-check.php
```

Acesse no navegador:
- **Site Institucional Dinâmico:** [http://localhost:8000](http://localhost:8000)
- **Painel CMS (Admin):** [http://localhost:8000/admin](http://localhost:8000/admin)

---

## 🔑 Credenciais do Painel Administrativo

- **E-mail:** `admin@drgeorgescapin.com.br`
- **Senha:** `admin123`

---

## 🏗️ Estrutura do Projeto na Raiz (Clean Architecture)

```
site_george/
├── app/
│   ├── Infrastructure/
│   │   ├── Database/Connection.php        ← Conexão PDO Singleton MySQL (utf8mb4)
│   │   ├── Repositories/                  ← Repositórios MySQL (Tratamentos, Leads, Conteúdos, Usuários)
│   │   └── Security/Auth.php              ← Gerenciador de Sessão e Autenticação BCRYPT
│   ├── Presentation/
│   │   ├── Controllers/                   ← SiteController, AdminController, AuthController
│   │   ├── Middlewares/                   ← AuthMiddleware (Proteção de rotas do admin)
│   │   └── Views/
│   │       ├── site/                      ← Páginas públicas dinâmicas (Home, Tratamentos, Clínica, Contato)
│   │       ├── admin/                     ← Telas do CMS (Dashboard, Tratamentos, Leads, SEO, Perfil)
│   │       └── layouts/                   ← Layouts base (Header, Footer, Admin Sidebar)
│   └── helpers.php                        ← Funções globais (asset, url, csrf_field, flash, redirect)
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
├── index.php                              ← Proxy raiz para execução direta
├── .htaccess                              ← Regra raiz de roteamento (padrão super_app)
├── .env                                   ← Configurações ativas do MySQL
├── .env.example                           ← Template de variáveis de ambiente
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
   - Página biográfica com a foto oficial do Dr. George Scapin (`drgeorge.jpeg`), titulação (CRBM 5202), filosofia de atendimento e citação em destaque.

3. **Procedimentos (`/procedimentos` ou `/tratamentos`)**:
   - Layout dividido (*split-screen*) alternado com fotos em alta resolução (`01.jpeg`, `02.jpeg`, `03.jpeg`, `04.jpeg`) e descrições detalhadas dos procedimentos (Toxina Botulínica, Prevenção de Rugas, Harmonização Facial, Harmonização Corporal).

4. **Harmonização Facial Full Face (`/harmonizacao-facial`)**:
   - *Landing page* focada no procedimento de Full Face, explicando a metodologia arquitetônica 360° e benefícios.

5. **Blog Dinâmico (`/blog`) e Artigos (`/blog/{slug}`)**:
   - Listagem de artigos educativos e posts completos consumidos diretamente do MySQL.

6. **Contato (`/contato`)**:
   - Formulário com proteção CSRF e persistência no banco de dados.

---

## 🎛️ Telas do Painel Administrativo (CMS)

1. **Dashboard Geral (`/admin`)**:
   - Indicadores em tempo real (Novos contatos, Procedimentos, Artigos no Blog, Inscritos na Newsletter).
   - Tabela de leads recentes com botão de ação rápida para chamar no **WhatsApp**.

2. **Gestão de Procedimentos (`/admin/procedures`)**:
   - CRUD completo com upload de imagens, seletor de ícones Lucide, ordenação e ativação.

3. **Gestão do Blog (`/admin/posts`)**:
   - Publicar novos artigos (`/admin/posts/create`), editar conteúdo com tags HTML, capa e publicar/rascunho.

4. **Central de Leads & Newsletter (`/admin/leads`)**:
   - Gerenciamento de status de contatos (`Novo`, `Atendido`, `Arquivado`).
   - Botões para **Exportar Leads e Assinantes em `.CSV`** para Excel.

5. **Conteúdos Institucionais & SEO (`/admin/settings`)**:
   - Edição de textos da Home, Biografia do Dr. George, telefones, endereço e metatags para o Google.

6. **Meu Perfil & Senha (`/admin/profile`)**:
   - Alteração de dados do administrador e troca de senha segura com BCRYPT.
