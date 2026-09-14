<?php

namespace App\Presentation\Controllers;

use App\Presentation\Middlewares\AuthMiddleware;
use App\Infrastructure\Repositories\PDOProcedureRepository;
use App\Infrastructure\Repositories\PDOSettingsRepository;
use App\Infrastructure\Repositories\PDOContentRepository;
use App\Infrastructure\Repositories\PDOLeadRepository;
use App\Infrastructure\Repositories\PDOUserRepository;
use App\Infrastructure\Repositories\PDOPostRepository;
use App\Infrastructure\Repositories\PDOMenuRepository;
use App\Infrastructure\Repositories\PDOCustomPageRepository;
use App\Infrastructure\Security\Auth;

class AdminController
{
    private PDOProcedureRepository $procedureRepo;
    private PDOSettingsRepository $settingsRepo;
    private PDOContentRepository $contentRepo;
    private PDOLeadRepository $leadRepo;
    private PDOUserRepository $userRepo;
    private PDOPostRepository $postRepo;
    private PDOMenuRepository $menuRepo;
    private PDOCustomPageRepository $customPageRepo;

    public function __construct()
    {
        AuthMiddleware::handle();

        // Verificação estrita de CSRF para todas as ações POST administrativas
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            if (!verify_csrf()) {
                $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || 
                          (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) ||
                          isset($_FILES['image']) || isset($_FILES['file']);
                
                if ($isAjax) {
                    json_response(['success' => false, 'error' => 'Token de segurança CSRF inválido ou expirado. Recarregue a página.'], 403);
                }
                flash('error', 'Token de segurança CSRF inválido ou expirado. Por favor, tente novamente.');
                $referer = $_SERVER['HTTP_REFERER'] ?? '/admin';
                redirect($referer);
                exit;
            }
        }

        $this->procedureRepo = new PDOProcedureRepository();
        $this->settingsRepo = new PDOSettingsRepository();
        $this->contentRepo = new PDOContentRepository();
        $this->leadRepo = new PDOLeadRepository();
        $this->userRepo = new PDOUserRepository();
        $this->postRepo = new PDOPostRepository();
        $this->menuRepo = new PDOMenuRepository();
        $this->customPageRepo = new PDOCustomPageRepository();
    }

    public function dashboard(): void
    {
        $user = Auth::user();
        $contacts = $this->leadRepo->getAllContacts();
        $subscribers = $this->leadRepo->getAllNewsletterSubscribers();
        $procedures = $this->procedureRepo->getAll(false);
        $posts = $this->postRepo->getAll(false);
        $newLeadsCount = $this->leadRepo->countNewLeads();

        $activeTab = 'dashboard';
        require admin_view_path('dashboard');
    }

    // ================= TRATAMENTOS =================
    public function procedures(): void
    {
        $user = Auth::user();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 5;
        $pagination = $this->procedureRepo->getPaginated($page, $perPage, false);

        $procedures = $pagination['data'];
        $currentPage = $pagination['current_page'];
        $totalPages = $pagination['last_page'];
        $totalProcedures = $pagination['total'];
        
        $activeTab = 'procedures';
        require admin_view_path('procedures');
    }

    public function procedureCreate(): void
    {
        $user = Auth::user();
        $procedure = null;
        $activeTab = 'procedures';
        require admin_view_path('procedure_form');
    }

    public function procedureStore(): void
    {
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $shortDesc = trim($_POST['short_description'] ?? '');
        $fullDesc = trim($_POST['full_description'] ?? '');
        $iconName = trim($_POST['icon_name'] ?? 'sparkles');
        $imageUrl = trim($_POST['image_url'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        }

        if (isset($_FILES['image_file'])) {
            $uploadedPath = $this->handleUpload($_FILES['image_file'], 'proc_');
            if ($uploadedPath) {
                $imageUrl = $uploadedPath;
            }
        }

        $this->procedureRepo->create([
            'title' => $title,
            'slug' => $slug,
            'short_description' => $shortDesc,
            'full_description' => $fullDesc,
            'icon_name' => $iconName,
            'image_url' => $imageUrl,
            'sort_order' => $sortOrder,
            'is_active' => $isActive
        ]);

        flash('success', 'Tratamento adicionado com sucesso!');
        redirect('admin/procedures');
    }

    public function procedureEdit(int $id): void
    {
        $user = Auth::user();
        $procedure = $this->procedureRepo->findById($id);
        if (!$procedure) {
            flash('error', 'Tratamento não encontrado.');
            redirect('admin/procedures');
        }

        $activeTab = 'procedures';
        require admin_view_path('procedure_form');
    }

    public function procedureUpdate(int $id): void
    {
        $procedure = $this->procedureRepo->findById($id);
        if (!$procedure) {
            flash('error', 'Tratamento não encontrado.');
            redirect('admin/procedures');
        }

        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $shortDesc = trim($_POST['short_description'] ?? '');
        $fullDesc = trim($_POST['full_description'] ?? '');
        $iconName = trim($_POST['icon_name'] ?? 'sparkles');
        $imageUrl = trim($_POST['image_url'] ?? $procedure['image_url']);
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        // Se o usuário marcou para remover a imagem
        if (!empty($_POST['remove_image'])) {
            $this->deleteUploadedFile($procedure['image_url'] ?? null);
            $imageUrl = '';
        }

        // Upload de nova imagem
        if (isset($_FILES['image_file'])) {
            $uploadedPath = $this->handleUpload($_FILES['image_file'], 'proc_');
            if ($uploadedPath) {
                // Remove imagem antiga se era um upload
                $this->deleteUploadedFile($procedure['image_url'] ?? null);
                $imageUrl = $uploadedPath;
            }
        }

        $this->procedureRepo->update($id, [
            'title' => $title,
            'slug' => $slug,
            'short_description' => $shortDesc,
            'full_description' => $fullDesc,
            'icon_name' => $iconName,
            'image_url' => $imageUrl,
            'sort_order' => $sortOrder,
            'is_active' => $isActive
        ]);

        flash('success', 'Tratamento atualizado com sucesso!');
        redirect('admin/procedures');
    }

    public function procedureDelete(int $id): void
    {
        $procedure = $this->procedureRepo->findById($id);
        if ($procedure) {
            $this->deleteUploadedFile($procedure['image_url'] ?? null);
            $this->procedureRepo->delete($id);
        }
        flash('success', 'Tratamento e imagem excluídos com sucesso.');
        redirect('admin/procedures');
    }

    public function procedureRemoveImage(int $id): void
    {
        $procedure = $this->procedureRepo->findById($id);
        if ($procedure) {
            $this->deleteUploadedFile($procedure['image_url'] ?? null);
            $this->procedureRepo->update($id, [
                'title' => $procedure['title'],
                'slug' => $procedure['slug'],
                'short_description' => $procedure['short_description'],
                'full_description' => $procedure['full_description'],
                'icon_name' => $procedure['icon_name'],
                'image_url' => '',
                'sort_order' => $procedure['sort_order'],
                'is_active' => $procedure['is_active']
            ]);
            flash('success', 'Imagem do tratamento removida com sucesso!');
        }
        redirect('admin/procedures/edit/' . $id);
    }

    // ================= BLOG POSTS =================
    public function posts(): void
    {
        $user = Auth::user();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 5;
        $pagination = $this->postRepo->getPaginated($page, $perPage, false);

        $posts = $pagination['data'];
        $currentPage = $pagination['current_page'];
        $totalPages = $pagination['last_page'];
        $totalPosts = $pagination['total'];

        $activeTab = 'posts';
        require admin_view_path('posts');
    }

    public function postCreate(): void
    {
        $user = Auth::user();
        $post = null;
        $activeTab = 'posts';
        require admin_view_path('post_form');
    }

    public function postStore(): void
    {
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $author = trim($_POST['author'] ?? 'Dr. George');
        $summary = trim($_POST['summary'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $imageUrl = trim($_POST['image_url'] ?? '');
        $isPublished = isset($_POST['is_published']) ? 1 : 0;

        if (empty($title)) {
            flash('error', 'O título do artigo é obrigatório.');
            redirect('admin/posts/create');
            return;
        }

        if (empty($slug)) {
            $slug = $this->slugify($title);
        } else {
            $slug = $this->slugify($slug);
        }

        // Garante slug único para evitar conflito com outros artigos
        $existing = $this->postRepo->findBySlug($slug);
        if ($existing) {
            $slug .= '-' . time();
        }

        if (isset($_FILES['image_file']) && !empty($_FILES['image_file']['tmp_name'])) {
            $uploadedPath = $this->handleUpload($_FILES['image_file'], 'post_');
            if ($uploadedPath) {
                $imageUrl = $uploadedPath;
            }
        }

        try {
            $this->postRepo->create([
                'title' => $title,
                'slug' => $slug,
                'author' => $author,
                'summary' => $summary,
                'content' => $content,
                'image_url' => $imageUrl,
                'is_published' => $isPublished
            ]);

            flash('success', 'Artigo publicado com sucesso no Blog!');
            redirect('admin/posts');
        } catch (\Throwable $e) {
            flash('error', 'Erro ao salvar artigo: ' . $e->getMessage());
            redirect('admin/posts/create');
        }
    }

    public function postEdit(int $id): void
    {
        $user = Auth::user();
        $post = $this->postRepo->findById($id);
        if (!$post) {
            flash('error', 'Artigo não encontrado.');
            redirect('admin/posts');
        }

        $activeTab = 'posts';
        require admin_view_path('post_form');
    }

    public function postUpdate(int $id): void
    {
        $post = $this->postRepo->findById($id);
        if (!$post) {
            flash('error', 'Artigo não encontrado.');
            redirect('admin/posts');
            return;
        }

        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $author = trim($_POST['author'] ?? 'Dr. George');
        $summary = trim($_POST['summary'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $imageUrl = trim($_POST['image_url'] ?? $post['image_url']);
        $isPublished = isset($_POST['is_published']) ? 1 : 0;

        if (empty($title)) {
            flash('error', 'O título do artigo é obrigatório.');
            redirect('admin/posts/edit/' . $id);
            return;
        }

        if (empty($slug)) {
            $slug = $this->slugify($title);
        } else {
            $slug = $this->slugify($slug);
        }

        // Se o slug mudou, verifica se já existe outro post com este slug
        if ($slug !== $post['slug']) {
            $existing = $this->postRepo->findBySlug($slug);
            if ($existing && (int)$existing['id'] !== $id) {
                $slug .= '-' . time();
            }
        }

        // Se o usuário marcou para remover a imagem
        if (!empty($_POST['remove_image'])) {
            $this->deleteUploadedFile($post['image_url'] ?? null);
            $imageUrl = '';
        }

        // Upload de nova imagem
        if (isset($_FILES['image_file']) && !empty($_FILES['image_file']['tmp_name'])) {
            $uploadedPath = $this->handleUpload($_FILES['image_file'], 'post_');
            if ($uploadedPath) {
                // Remove imagem antiga se era um upload
                $this->deleteUploadedFile($post['image_url'] ?? null);
                $imageUrl = $uploadedPath;
            }
        }

        try {
            $this->postRepo->update($id, [
                'title' => $title,
                'slug' => $slug,
                'author' => $author,
                'summary' => $summary,
                'content' => $content,
                'image_url' => $imageUrl,
                'is_published' => $isPublished
            ]);

            flash('success', 'Artigo atualizado com sucesso!');
            redirect('admin/posts');
        } catch (\Throwable $e) {
            flash('error', 'Erro ao atualizar artigo: ' . $e->getMessage());
            redirect('admin/posts/edit/' . $id);
        }
    }

    public function postDelete(int $id): void
    {
        $post = $this->postRepo->findById($id);
        if ($post) {
            $this->deleteUploadedFile($post['image_url'] ?? null);
            $this->postRepo->delete($id);
        }
        flash('success', 'Artigo e imagem excluídos do blog.');
        redirect('admin/posts');
    }

    public function postRemoveImage(int $id): void
    {
        $post = $this->postRepo->findById($id);
        if ($post) {
            $this->deleteUploadedFile($post['image_url'] ?? null);
            $this->postRepo->update($id, [
                'title' => $post['title'],
                'slug' => $post['slug'],
                'author' => $post['author'],
                'summary' => $post['summary'],
                'content' => $post['content'],
                'image_url' => '',
                'is_published' => $post['is_published']
            ]);
            flash('success', 'Imagem do artigo removida com sucesso!');
        }
        redirect('admin/posts/edit/' . $id);
    }

    // ================= LEADS & CONTATOS =================
    public function leads(): void
    {
        $user = Auth::user();
        
        $contactsPage = max(1, (int)($_GET['contacts_page'] ?? 1));
        $contactsPagination = $this->leadRepo->getContactsPaginated($contactsPage, 5);
        $contacts = $contactsPagination['data'];
        $contactsCurrentPage = $contactsPagination['current_page'];
        $contactsTotalPages = $contactsPagination['last_page'];
        $contactsTotal = $contactsPagination['total'];

        $newsPage = max(1, (int)($_GET['news_page'] ?? 1));
        $newsPagination = $this->leadRepo->getNewsletterPaginated($newsPage, 5);
        $subscribers = $newsPagination['data'];
        $newsCurrentPage = $newsPagination['current_page'];
        $newsTotalPages = $newsPagination['last_page'];
        $newsTotal = $newsPagination['total'];

        $activeTab = 'leads';
        require admin_view_path('leads');
    }

    public function updateLeadStatus(int $id): void
    {
        $status = $_POST['status'] ?? 'novo';
        $this->leadRepo->updateContactStatus($id, $status);
        flash('success', 'Status do contato atualizado!');
        redirect('admin/leads');
    }

    public function exportLeadsCsv(): void
    {
        $contacts = $this->leadRepo->getAllContacts();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=leads_clinica_george_' . date('Y-m-d') . '.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Data', 'Nome', 'Telefone', 'Mensagem', 'Status'], ',', '"', "\\");
        foreach ($contacts as $c) {
            fputcsv($output, [$c['id'], $c['created_at'], $c['nome'], $c['telefone'], $c['mensagem'], $c['status']], ',', '"', "\\");
        }
        fclose($output);
        exit;
    }

    public function exportNewsletterCsv(): void
    {
        $subscribers = $this->leadRepo->getAllNewsletterSubscribers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=newsletter_subscribers_' . date('Y-m-d') . '.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Email', 'Data Cadastro', 'Status'], ',', '"', "\\");
        foreach ($subscribers as $s) {
            fputcsv($output, [$s['id'], $s['email'], $s['created_at'], $s['status']], ',', '"', "\\");
        }
        fclose($output);
        exit;
    }

    // ================= CONTEÚDOS & SEO =================
    public function settings(): void
    {
        $user = Auth::user();
        $settings = $this->settingsRepo->getAll();
        $hero = $this->contentRepo->getHeroContent();
        $clinic = $this->contentRepo->getClinicContent();

        $activeTab = 'settings';
        require admin_view_path('settings');
    }

    public function updateSettings(): void
    {
        $settingsKeys = [
            'site_title', 'meta_description', 'meta_keywords',
            'contact_phone', 'contact_whatsapp', 'contact_address',
            'contact_hours_week', 'contact_hours_sat',
            'footer_tagline', 'footer_copyright', 'social_instagram',
            'procedures_page_title', 'procedures_page_subtitle',
            'blog_page_title', 'blog_page_subtitle', 'blog_cta_title', 'blog_cta_text',
            'contact_page_title', 'contact_page_subtitle'
        ];

        foreach ($settingsKeys as $key) {
            if (isset($_POST[$key])) {
                $this->settingsRepo->set($key, trim($_POST[$key]));
            }
        }

        if (isset($_POST['hero_title'])) {
            $this->contentRepo->updateHeroContent([
                'title' => $_POST['hero_title'] ?? '',
                'subtitle' => $_POST['hero_subtitle'] ?? '',
                'button_text' => $_POST['hero_button_text'] ?? 'Agendar Consulta',
                'button_link' => $_POST['hero_button_link'] ?? '/contato',
                'bg_image_dark' => $_POST['hero_bg_dark'] ?? '/assets/images/hero.png',
                'bg_image_light' => $_POST['hero_bg_light'] ?? '/assets/images/hero_light.png'
            ]);
        }

        if (isset($_POST['clinic_title'])) {
            $this->contentRepo->updateClinicContent([
                'title' => $_POST['clinic_title'] ?? '',
                'subtitle' => $_POST['clinic_subtitle'] ?? '',
                'paragraph_1' => $_POST['clinic_p1'] ?? '',
                'paragraph_2' => $_POST['clinic_p2'] ?? '',
                'highlight_quote' => $_POST['clinic_quote'] ?? '',
                'image_url' => $_POST['clinic_image'] ?? '/assets/img/drgeorge.jpeg'
            ]);
        }

        flash('success', 'Configurações e conteúdos salvos com sucesso!');
        redirect('admin/settings');
    }

    // ================= PÁGINAS DO SITE =================
    public function pages(): void
    {
        $user = Auth::user();
        $settings = $this->settingsRepo->getAll();
        $hero = $this->contentRepo->getHeroContent();
        $clinic = $this->contentRepo->getClinicContent();
        $totalProcedures = count($this->procedureRepo->getAll(false));
        $totalPosts = count($this->postRepo->getAll(false));
        $customPages = $this->customPageRepo->getAll(false);

        $pagesList = [
            [
                'id' => 'home',
                'name' => 'Página Inicial (Home)',
                'slug' => '/',
                'url' => url('/'),
                'edit_url' => url('/admin/pages/home'),
                'delete_url' => url('/admin/pages/delete/home'),
                'toggle_url' => url('/admin/pages/toggle-status/home'),
                'description' => 'Hero principal, 3 pilares de atendimento, tratamentos em destaque, dúvidas frequentes.',
                'image' => '/assets/images/hero.png',
                'badge' => 'Principal',
                'status' => ($settings['page_status_home'] ?? 'active') === 'inactive' ? 'Inativa' : 'Publicada',
                'is_system' => true
            ],
            [
                'id' => 'clinic',
                'name' => 'George Scapin / A Clínica',
                'slug' => '/clinica',
                'url' => url('/clinica'),
                'edit_url' => url('/admin/pages/clinic'),
                'delete_url' => url('/admin/pages/delete/clinic'),
                'toggle_url' => url('/admin/pages/toggle-status/clinic'),
                'description' => 'Apresentação institucional do Dr. George Scapin (CRBM 5202), filosofia de naturalidade e biografia.',
                'image' => $clinic['image_url'] ?? '/assets/img/drgeorge.jpeg',
                'badge' => 'Institucional',
                'status' => ($settings['page_status_clinic'] ?? 'active') === 'inactive' ? 'Inativa' : 'Publicada',
                'is_system' => true
            ],
            [
                'id' => 'procedures',
                'name' => 'Procedimentos & Tratamentos',
                'slug' => '/procedimentos',
                'url' => url('/procedimentos'),
                'edit_url' => url('/admin/procedures'),
                'delete_url' => url('/admin/pages/delete/procedures'),
                'toggle_url' => url('/admin/pages/toggle-status/procedures'),
                'description' => 'Layout Split-Screen interativo com todos os tratamentos cadastrados (' . $totalProcedures . ' itens).',
                'image' => '/assets/botox.png',
                'badge' => 'Dinâmica',
                'status' => ($settings['page_status_procedures'] ?? 'active') === 'inactive' ? 'Inativa' : 'Publicada',
                'is_system' => true
            ],
            [
                'id' => 'harmonization',
                'name' => 'Harmonização Facial Full Face',
                'slug' => '/harmonizacao-facial',
                'url' => url('/harmonizacao-facial'),
                'edit_url' => url('/admin/pages/harmonization'),
                'delete_url' => url('/admin/pages/delete/harmonization'),
                'toggle_url' => url('/admin/pages/toggle-status/harmonization'),
                'description' => 'Landing page dedicada ao conceito, anatomia tridimensional e metodologia do Full Face.',
                'image' => $settings['harmonization_image'] ?? '/assets/fullface.png',
                'badge' => 'Especialidade',
                'status' => ($settings['page_status_harmonization'] ?? 'active') === 'inactive' ? 'Inativa' : 'Publicada',
                'is_system' => true
            ],
            [
                'id' => 'blog',
                'name' => 'Blog Educativo (Listagem)',
                'slug' => '/blog',
                'url' => url('/blog'),
                'edit_url' => url('/admin/posts'),
                'delete_url' => url('/admin/pages/delete/blog'),
                'toggle_url' => url('/admin/pages/toggle-status/blog'),
                'description' => 'Listagem de artigos educativos sobre rejuvenescimento com paginação (' . $totalPosts . ' artigos).',
                'image' => '/assets/clinic.png',
                'badge' => 'Artigos',
                'status' => ($settings['page_status_blog'] ?? 'active') === 'inactive' ? 'Inativa' : 'Publicada',
                'is_system' => true
            ],
            [
                'id' => 'blog_single',
                'name' => 'Artigo do Blog (Template Dinâmico)',
                'slug' => '/blog/{slug}',
                'url' => url('/blog'),
                'edit_url' => url('/admin/posts'),
                'delete_url' => url('/admin/pages/delete/blog_single'),
                'toggle_url' => url('/admin/pages/toggle-status/blog_single'),
                'description' => 'Template de leitura de artigos individuais com banner, texto rico formatado e chamada para consulta.',
                'image' => '/assets/images/botox.png',
                'badge' => 'Dinâmica',
                'status' => 'Publicada',
                'is_system' => true
            ],
            [
                'id' => 'contact',
                'name' => 'Contato & Agendamento',
                'slug' => '/contato',
                'url' => url('/contato'),
                'edit_url' => url('/admin/settings'),
                'delete_url' => url('/admin/pages/delete/contact'),
                'toggle_url' => url('/admin/pages/toggle-status/contact'),
                'description' => 'Formulário de pré-agendamento VIP, mapa, canais de WhatsApp e telefones.',
                'image' => '/assets/images/logo.svg',
                'badge' => 'Conversão',
                'status' => ($settings['page_status_contact'] ?? 'active') === 'inactive' ? 'Inativa' : 'Publicada',
                'is_system' => true
            ],
            [
                'id' => '404',
                'name' => 'Página 404 (Não Encontrada)',
                'slug' => '/404',
                'url' => url('/pagina-inexistente-teste'),
                'edit_url' => url('/admin/settings'),
                'delete_url' => url('/admin/pages/delete/404'),
                'toggle_url' => url('/admin/pages/toggle-status/404'),
                'description' => 'Tela apresentada quando o visitante digita uma URL inexistente, com botão de retorno à Home.',
                'image' => '/assets/images/logo.svg',
                'badge' => 'Sistema',
                'status' => 'Ativa',
                'is_system' => true
            ]
        ];

        $activeTab = 'pages';
        require admin_view_path('pages');
    }

    public function pageHome(): void
    {
        $user = Auth::user();
        $hero = $this->contentRepo->getHeroContent();
        $settings = $this->settingsRepo->getAll();
        $activeTab = 'pages';
        require admin_view_path('page_home');
    }

    public function updatePageHome(): void
    {
        $hero = $this->contentRepo->getHeroContent();
        $bgDark = $hero['bg_image_dark'] ?? '/assets/images/hero.png';
        $bgLight = $hero['bg_image_light'] ?? '/assets/images/hero_light.png';

        if (isset($_FILES['bg_dark_file'])) {
            $uploadedDark = $this->handleUpload($_FILES['bg_dark_file'], 'hero_dark_');
            if ($uploadedDark) {
                $this->deleteUploadedFile($bgDark);
                $bgDark = $uploadedDark;
            }
        }

        if (isset($_FILES['bg_light_file'])) {
            $uploadedLight = $this->handleUpload($_FILES['bg_light_file'], 'hero_light_');
            if ($uploadedLight) {
                $this->deleteUploadedFile($bgLight);
                $bgLight = $uploadedLight;
            }
        }

        $this->contentRepo->updateHeroContent([
            'title' => $_POST['hero_title'] ?? '',
            'subtitle' => $_POST['hero_subtitle'] ?? '',
            'button_text' => $_POST['hero_button_text'] ?? 'Agendar Consulta',
            'button_link' => $_POST['hero_button_link'] ?? '/contato',
            'bg_image_dark' => $bgDark,
            'bg_image_light' => $bgLight
        ]);

        // Salva os 3 pilares, seção de tratamentos e banner de queixas / FAQ
        $homeFields = [
            'home_pillar1_title', 'home_pillar1_text',
            'home_pillar2_title', 'home_pillar2_text',
            'home_pillar3_title', 'home_pillar3_text',
            'home_services_title', 'home_services_subtitle',
            'home_faq_tag', 'home_faq_title', 'home_faq_subtitle', 'home_faq_btn'
        ];

        foreach ($homeFields as $field) {
            if (isset($_POST[$field])) {
                $this->settingsRepo->set($field, trim($_POST[$field]));
            }
        }

        flash('success', 'Conteúdo da Página Inicial (Home) atualizado com sucesso!');
        redirect('admin/pages');
    }

    public function pageClinic(): void
    {
        $user = Auth::user();
        $clinic = $this->contentRepo->getClinicContent();
        $settings = $this->settingsRepo->getAll();
        $activeTab = 'pages';
        require admin_view_path('page_clinic');
    }

    public function updatePageClinic(): void
    {
        $clinic = $this->contentRepo->getClinicContent();
        $imageUrl = $clinic['image_url'] ?? '/assets/img/drgeorge.jpeg';

        if (!empty($_POST['remove_image'])) {
            $this->deleteUploadedFile($imageUrl);
            $imageUrl = '';
        }

        if (isset($_FILES['image_file'])) {
            $uploaded = $this->handleUpload($_FILES['image_file'], 'dr_george_');
            if ($uploaded) {
                $this->deleteUploadedFile($imageUrl);
                $imageUrl = $uploaded;
            }
        }

        $this->contentRepo->updateClinicContent([
            'title' => $_POST['clinic_title'] ?? '',
            'subtitle' => $_POST['clinic_subtitle'] ?? '',
            'paragraph_1' => $_POST['clinic_p1'] ?? '',
            'paragraph_2' => $_POST['clinic_p2'] ?? '',
            'highlight_quote' => $_POST['clinic_quote'] ?? '',
            'image_url' => $imageUrl
        ]);

        if (isset($_POST['clinic_section_title'])) {
            $this->settingsRepo->set('clinic_section_title', trim($_POST['clinic_section_title']));
        }
        if (isset($_POST['clinic_cta_btn'])) {
            $this->settingsRepo->set('clinic_cta_btn', trim($_POST['clinic_cta_btn']));
        }

        flash('success', 'Conteúdo da Página "George Scapin / A Clínica" atualizado com sucesso!');
        redirect('admin/pages');
    }

    // ================= PÁGINA HARMONIZAÇÃO FACIAL =================
    public function pageHarmonization(): void
    {
        $user = Auth::user();
        $settings = $this->settingsRepo->getAll();
        $activeTab = 'pages';
        require admin_view_path('page_harmonization');
    }

    public function updatePageHarmonization(): void
    {
        $settings = $this->settingsRepo->getAll();
        $image = $settings['harmonization_image'] ?? '/assets/fullface.png';

        if (!empty($_POST['remove_image'])) {
            $this->deleteUploadedFile($image);
            $image = '/assets/fullface.png';
        }

        if (isset($_FILES['harmonization_image_file'])) {
            $uploaded = $this->handleUpload($_FILES['harmonization_image_file'], 'harmonization_');
            if ($uploaded) {
                $this->deleteUploadedFile($image);
                $image = $uploaded;
            }
        }

        $fields = [
            'harmonization_title',
            'harmonization_subtitle',
            'harmonization_intro',
            'harmonization_section_title',
            'harmonization_p1',
            'harmonization_p2',
            'harmonization_feature1_title',
            'harmonization_feature1_text',
            'harmonization_feature2_title',
            'harmonization_feature2_text',
            'harmonization_cta_title',
            'harmonization_cta_text',
            'harmonization_cta_btn'
        ];

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $this->settingsRepo->set($field, trim($_POST[$field]));
            }
        }

        $this->settingsRepo->set('harmonization_image', $image);

        flash('success', 'Conteúdo da página "Harmonização Facial Full Face" salvo com sucesso!');
        redirect('admin/pages');
    }

    // ================= EXCLUSÃO / DESATIVAÇÃO DE PÁGINAS NA LINHA =================
    public function deletePage(string $id): void
    {
        // Se for página customizada (ex: número ou id com prefixo cp_)
        if (is_numeric($id)) {
            $this->customPageRepo->delete((int)$id);
            flash('success', 'Página personalizada excluída com sucesso!');
            redirect('admin/pages');
        }

        if (strpos($id, 'cp_') === 0) {
            $realId = (int)str_replace('cp_', '', $id);
            $this->customPageRepo->delete($realId);
            flash('success', 'Página personalizada excluída com sucesso!');
            redirect('admin/pages');
        }

        // Se for página do sistema, desativa ou reseta
        $systemPages = ['home', 'clinic', 'procedures', 'harmonization', 'blog', 'blog_single', 'contact', '404'];
        if (in_array($id, $systemPages, true)) {
            $currentStatus = $this->settingsRepo->get("page_status_{$id}", 'active');
            $newStatus = ($currentStatus === 'active') ? 'inactive' : 'active';
            $this->settingsRepo->set("page_status_{$id}", $newStatus);

            $statusLabel = ($newStatus === 'inactive') ? 'desativada / ocultada' : 'reativada';
            flash('success', "A página foi {$statusLabel} com sucesso!");
            redirect('admin/pages');
        }

        flash('error', 'Página não encontrada.');
        redirect('admin/pages');
    }

    public function togglePageStatus(string $id): void
    {
        $this->deletePage($id);
    }

    // ================= MENU DE NAVEGAÇÃO =================
    public function menu(): void
    {
        $user = Auth::user();
        $menuItems = $this->menuRepo->getAll(false);
        $customPages = $this->customPageRepo->getAll(true);

        $activeTab = 'menu';
        require admin_view_path('menu');
    }

    public function menuStore(): void
    {
        $label = trim($_POST['label'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $target = $_POST['target'] ?? '_self';
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $isButton = isset($_POST['is_button']) ? 1 : 0;

        if (empty($label) || empty($url)) {
            flash('error', 'Nome do link e URL são obrigatórios.');
            redirect('admin/menu');
        }

        $this->menuRepo->create([
            'label' => $label,
            'url' => $url,
            'target' => $target,
            'sort_order' => $sortOrder,
            'is_active' => $isActive,
            'is_button' => $isButton
        ]);

        flash('success', 'Item adicionado ao menu de navegação com sucesso!');
        redirect('admin/menu');
    }

    public function menuUpdate(int $id): void
    {
        $item = $this->menuRepo->findById($id);
        if (!$item) {
            flash('error', 'Item de menu não encontrado.');
            redirect('admin/menu');
        }

        $label = trim($_POST['label'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $target = $_POST['target'] ?? '_self';
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $isButton = isset($_POST['is_button']) ? 1 : 0;

        if (empty($label) || empty($url)) {
            flash('error', 'Nome do link e URL são obrigatórios.');
            redirect('admin/menu');
        }

        $this->menuRepo->update($id, [
            'label' => $label,
            'url' => $url,
            'target' => $target,
            'sort_order' => $sortOrder,
            'is_active' => $isActive,
            'is_button' => $isButton
        ]);

        flash('success', 'Item de menu atualizado com sucesso!');
        redirect('admin/menu');
    }

    public function menuDelete(int $id): void
    {
        $this->menuRepo->delete($id);
        flash('success', 'Item removido do menu de navegação.');
        redirect('admin/menu');
    }

    // ================= PÁGINAS CUSTOMIZADAS =================
    public function customPages(): void
    {
        $this->pages();
    }

    public function customPageCreate(): void
    {
        $user = Auth::user();
        $page = null;
        $activeTab = 'pages';
        require admin_view_path('custom_page_form');
    }

    public function customPageStore(): void
    {
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $metaDescription = trim($_POST['meta_description'] ?? '');
        $bannerImage = '';
        $isPublished = isset($_POST['is_published']) ? 1 : 0;
        $addToMenu = isset($_POST['add_to_menu']) ? 1 : 0;

        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        }

        if (isset($_FILES['banner_file'])) {
            $uploaded = $this->handleUpload($_FILES['banner_file'], 'page_banner_');
            if ($uploaded) {
                $bannerImage = $uploaded;
            }
        }

        $pageId = $this->customPageRepo->create([
            'title' => $title,
            'slug' => $slug,
            'subtitle' => $subtitle,
            'content' => $content,
            'banner_image' => $bannerImage,
            'meta_description' => $metaDescription,
            'is_published' => $isPublished
        ]);

        // Se marcado para adicionar automaticamente ao menu
        if ($addToMenu) {
            $totalMenuItems = count($this->menuRepo->getAll(false));
            $this->menuRepo->create([
                'label' => $title,
                'url' => '/p/' . $slug,
                'target' => '_self',
                'sort_order' => $totalMenuItems + 1,
                'is_active' => 1,
                'is_button' => 0
            ]);
        }

        flash('success', 'Nova página criada com sucesso!');
        redirect('admin/pages');
    }

    public function customPageEdit(int $id): void
    {
        $user = Auth::user();
        $page = $this->customPageRepo->findById($id);
        if (!$page) {
            flash('error', 'Página não encontrada.');
            redirect('admin/pages');
        }

        $activeTab = 'pages';
        require admin_view_path('custom_page_form');
    }

    public function customPageUpdate(int $id): void
    {
        $page = $this->customPageRepo->findById($id);
        if (!$page) {
            flash('error', 'Página não encontrada.');
            redirect('admin/pages');
        }

        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $metaDescription = trim($_POST['meta_description'] ?? '');
        $bannerImage = trim($_POST['banner_image'] ?? ($page['banner_image'] ?? ''));
        $isPublished = isset($_POST['is_published']) ? 1 : 0;

        if (!empty($_POST['remove_banner'])) {
            $this->deleteUploadedFile($page['banner_image'] ?? null);
            $bannerImage = '';
        }

        if (isset($_FILES['banner_file'])) {
            $uploaded = $this->handleUpload($_FILES['banner_file'], 'page_banner_');
            if ($uploaded) {
                $this->deleteUploadedFile($page['banner_image'] ?? null);
                $bannerImage = $uploaded;
            }
        }

        $this->customPageRepo->update($id, [
            'title' => $title,
            'slug' => $slug,
            'subtitle' => $subtitle,
            'content' => $content,
            'banner_image' => $bannerImage,
            'meta_description' => $metaDescription,
            'is_published' => $isPublished
        ]);

        flash('success', 'Página atualizada com sucesso!');
        redirect('admin/pages');
    }

    public function customPageDelete(int $id): void
    {
        $page = $this->customPageRepo->findById($id);
        if ($page) {
            $this->deleteUploadedFile($page['banner_image'] ?? null);
            $this->customPageRepo->delete($id);
        }
        flash('success', 'Página excluída com sucesso.');
        redirect('admin/pages');
    }

    // ================= DESIGN SYSTEM =================
    public function designSystem(): void
    {
        $user = Auth::user();
        $activeTab = 'design-system';
        require admin_view_path('design_system');
    }

    // ================= PERFIL =================
    public function profile(): void
    {
        $user = Auth::user();
        $userData = $this->userRepo->findById((int)$user['id']);

        $activeTab = 'profile';
        require admin_view_path('profile');
    }

    public function updateProfile(): void
    {
        $user = Auth::user();
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($name) || empty($email)) {
            flash('error', 'Nome e e-mail são obrigatórios.');
            redirect('admin/profile');
        }

        $this->userRepo->updateProfile((int)$user['id'], $name, $email);
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;

        if (!empty($newPassword)) {
            $currentPassword = $_POST['current_password'] ?? '';
            $userDb = $this->userRepo->findWithPasswordById((int)$user['id']);

            if (empty($currentPassword) || !$userDb || !password_verify($currentPassword, $userDb['password'])) {
                flash('error', 'A senha atual informada está incorreta.');
                redirect('admin/profile');
                return;
            }

            if ($newPassword !== $confirmPassword) {
                flash('error', 'As novas senhas não coincidem.');
                redirect('admin/profile');
                return;
            }
            if (strlen($newPassword) < 6) {
                flash('error', 'A nova senha deve ter no mínimo 6 caracteres.');
                redirect('admin/profile');
                return;
            }
            $hash = password_hash($newPassword, PASSWORD_BCRYPT);
            $this->userRepo->updatePassword((int)$user['id'], $hash);
        }

        flash('success', 'Perfil e credenciais atualizados com sucesso!');
        redirect('admin/profile');
    }

    public function uploadMedia(): void
    {
        $file = $_FILES['image'] ?? $_FILES['file'] ?? null;
        if (!$file) {
            json_response(['success' => false, 'error' => 'Nenhum arquivo enviado.'], 400);
        }

        $url = $this->handleUpload($file, 'editor_');
        if ($url) {
            json_response(['success' => true, 'url' => $url]);
        } else {
            json_response(['success' => false, 'error' => 'Falha ao processar ou otimizar a imagem.'], 400);
        }
    }

    private function handleUpload(array $file, string $prefix = 'up_'): ?string
    {
        if (empty($file['tmp_name']) || !isset($file['error'])) {
            return null;
        }

        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
            flash('error', 'O arquivo de imagem enviado é muito pesado para o servidor.');
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'Erro no upload da imagem (Código: ' . $file['error'] . ').');
            return null;
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'bmp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExtensions)) {
            flash('error', 'Formato de imagem "' . htmlspecialchars($ext) . '" não suportado. Formatos aceitos: JPG, PNG, WebP, GIF, SVG.');
            return null;
        }

        $destinationDir = realpath(__DIR__ . '/../../../public') . '/uploads';
        if (!is_dir($destinationDir)) {
            @mkdir($destinationDir, 0777, true);
        }

        // Se for SVG, sanitiza o conteúdo para prevenir Stored XSS e XXE
        if ($ext === 'svg') {
            $svgContent = @file_get_contents($file['tmp_name']);
            if ($svgContent === false || stripos($svgContent, '<svg') === false) {
                flash('error', 'Arquivo SVG inválido.');
                return null;
            }

            // Remove scripts maliciosos, eventos inline (onload, onerror, etc) e entidades externas (XXE)
            $svgContent = preg_replace('/<\s*script[^>]*>.*?<\s*\/\s*script\s*>/is', '', $svgContent);
            $svgContent = preg_replace('/on[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/is', '', $svgContent);
            $svgContent = preg_replace('/href\s*=\s*["\']\s*javascript:[^"\']*["\']/is', '', $svgContent);
            $svgContent = preg_replace('/<!DOCTYPE[^>]*>/is', '', $svgContent);
            $svgContent = preg_replace('/<!ENTITY[^>]*>/is', '', $svgContent);

            $filename = uniqid($prefix) . '.svg';
            $destination = $destinationDir . '/' . $filename;
            if (file_put_contents($destination, $svgContent) !== false) {
                @chmod($destination, 0644);
                return '/uploads/' . $filename;
            }
            flash('error', 'Falha ao salvar o arquivo SVG.');
            return null;
        }

        // Para todas as imagens (JPG, PNG, WEBP, GIF, BMP):
        // Converte automaticamente para WebP de alta fidelidade e redimensiona para o tamanho ideal
        $filename = uniqid($prefix) . '.webp';
        $destination = $destinationDir . '/' . $filename;

        $optimized = \App\Infrastructure\Services\ImageOptimizer::processAndConvertToWebp(
            $file['tmp_name'],
            $destination,
            1400, // Largura máxima ideal
            1000, // Altura máxima ideal
            85    // Qualidade WebP
        );

        if ($optimized) {
            return '/uploads/' . $filename;
        } else {
            // Fallback caso a imagem tenha algum header atípico
            $fallbackFilename = uniqid($prefix) . '.' . $ext;
            $fallbackDestination = $destinationDir . '/' . $fallbackFilename;
            if (move_uploaded_file($file['tmp_name'], $fallbackDestination)) {
                @chmod($fallbackDestination, 0644);
                return '/uploads/' . $fallbackFilename;
            }
            flash('error', 'Falha ao processar e salvar a imagem.');
            return null;
        }
    }

    private function deleteUploadedFile(?string $path): void
    {
        if (empty($path)) return;

        // Só processa arquivos da pasta /uploads/
        if (strpos($path, '/uploads/') === 0) {
            $filename = basename($path);
            // Proteção contra Path Traversal: impede caracteres como .., / ou \ no nome do arquivo
            if (empty($filename) || $filename === '.' || $filename === '..' || preg_match('/[\/\\\\]/', $filename)) {
                return;
            }

            $destinationDir = realpath(__DIR__ . '/../../../public/uploads');
            if ($destinationDir && is_dir($destinationDir)) {
                $fullPath = $destinationDir . '/' . $filename;
                if (file_exists($fullPath) && is_file($fullPath) && realpath($fullPath) === $fullPath) {
                    @unlink($fullPath);
                }
            }
        }
    }

    private function slugify(string $text): string
    {
        if (function_exists('slugify')) {
            return \slugify($text);
        }
        $text = preg_replace('~[^\\pL\\d]+~u', '-', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        return empty($text) ? 'n-a' : $text;
    }
}
