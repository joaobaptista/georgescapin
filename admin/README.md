# 🎛️ Painel Administrativo CMS - Dr. George Scapin

Esta pasta na raiz do projeto centraliza todos os arquivos, templates e layouts do Painel Administrativo.

### 🧭 Estrutura da pasta `admin/`:

```
admin/
├── index.php                 ← Ponto de entrada web do Admin
├── layouts/
│   └── admin_layout.php      ← Template base (Sidebar, Topbar, Modais, Estilos)
├── views/
│   ├── dashboard.php         ← Métricas gerais e leads recentes
│   ├── procedures.php        ← Listagem de procedimentos
│   ├── procedure_form.php    ← Formulário de criação/edição de tratamentos
│   ├── posts.php             ← Listagem de posts do blog
│   ├── post_form.php         ← Editor de artigos (Quill Editor)
│   ├── pages.php             ← Painel de páginas dinâmicas
│   ├── page_home.php         ← Edição de textos da Home
│   ├── page_clinic.php       ← Edição de textos da página Dr. George
│   ├── custom_pages.php      ← Listagem de páginas personalizadas
│   ├── custom_page_form.php  ← Criar/editar landing pages e páginas extras
│   ├── menu.php              ← Gerenciador de itens do menu
│   ├── design_system.php     ← Guia de estilo e componentes UI
│   ├── leads.php             ← Gestão de contatos e exportação CSV
│   ├── settings.php          ← Telefones, endereços, redes sociais e SEO
│   ├── profile.php           ← Perfil do administrador e troca de senha
│   └── login.php             ← Tela de autenticação do CMS
└── README.md                 ← Este guia
```

### ⚙️ Lógica & Integração:
- **Controlador do Painel:** [`app/Presentation/Controllers/AdminController.php`](file:///app/Presentation/Controllers/AdminController.php)
- **Controlador de Autenticação:** [`app/Presentation/Controllers/AuthController.php`](file:///app/Presentation/Controllers/AuthController.php)
- **Rotas:** [`config/routes.php`](file:///config/routes.php)
- **Helper de Views:** `admin_view_path()` em [`app/helpers.php`](file:///app/helpers.php)
