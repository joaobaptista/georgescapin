<?php

namespace App\Presentation\Controllers;

use App\Infrastructure\Repositories\PDOProcedureRepository;
use App\Infrastructure\Repositories\PDOSettingsRepository;
use App\Infrastructure\Repositories\PDOContentRepository;
use App\Infrastructure\Repositories\PDOLeadRepository;
use App\Infrastructure\Repositories\PDOPostRepository;

class SiteController
{
    private PDOProcedureRepository $procedureRepo;
    private PDOSettingsRepository $settingsRepo;
    private PDOContentRepository $contentRepo;
    private PDOLeadRepository $leadRepo;
    private PDOPostRepository $postRepo;

    public function __construct()
    {
        $this->procedureRepo = new PDOProcedureRepository();
        $this->settingsRepo = new PDOSettingsRepository();
        $this->contentRepo = new PDOContentRepository();
        $this->leadRepo = new PDOLeadRepository();
        $this->postRepo = new PDOPostRepository();
    }

    public function index(): void
    {
        $hero = $this->contentRepo->getHeroContent();
        $procedures = $this->procedureRepo->getAll(true);
        $settings = $this->settingsRepo->getAll();
        
        $pageTitle = $settings['site_title'] ?? 'Dr. George Scapin | Harmonização e Estética Facial Avançada em RS';
        $metaDescription = $settings['meta_description'] ?? 'Clínica Dr. George Scapin em Porto Alegre. Especialista em Estética Facial, Toxina Botulínica, Preenchimento e Harmonização Full Face.';
        $activePage = 'home';

        require __DIR__ . '/../Views/site/home.php';
    }

    public function clinic(): void
    {
        $clinic = $this->contentRepo->getClinicContent();
        $settings = $this->settingsRepo->getAll();
        
        $pageTitle = 'Dr. George Scapin | Biomédico Esteta em Porto Alegre RS';
        $metaDescription = 'Conheça a trajetória do Dr. George Scapin (CRBM 5202), especialista em gerenciamento do envelhecimento e estética facial avançada.';
        $activePage = 'clinic';

        require __DIR__ . '/../Views/site/clinic.php';
    }

    public function procedures(): void
    {
        $procedures = $this->procedureRepo->getAll(true);
        $settings = $this->settingsRepo->getAll();
        
        $pageTitle = 'Procedimentos Estéticos e Harmonização | Dr. George Scapin RS';
        $metaDescription = 'Conheça todos os procedimentos do Dr. George Scapin: Toxina Botulínica, Preenchimento Facial, Harmonização Facial e Corporal em Porto Alegre.';
        $activePage = 'procedures';

        require __DIR__ . '/../Views/site/procedures.php';
    }

    public function harmonization(): void
    {
        $settings = $this->settingsRepo->getAll();
        
        $pageTitle = 'Harmonização Facial Full Face | Dr. George Scapin Porto Alegre';
        $metaDescription = 'Descubra a Harmonização Facial Full Face com o Dr. George Scapin. Realce sua beleza natural com proporção e segurança.';
        $activePage = 'harmonization';

        require __DIR__ . '/../Views/site/harmonization.php';
    }

    public function blog(): void
    {
        $currentPage = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 3;
        $pagination = $this->postRepo->getPaginated($currentPage, $perPage, true);

        $posts = $pagination['data'];
        $totalPages = $pagination['last_page'];
        $totalPosts = $pagination['total'];
        $settings = $this->settingsRepo->getAll();
        
        $pageTitle = 'Blog de Estética Avançada e Harmonização | Dr. George Scapin';
        $metaDescription = 'Artigos, dicas e novidades sobre Estética Facial, Toxina Botulínica, Cuidados com a Pele e Harmonização por Dr. George Scapin.';
        $activePage = 'blog';

        require __DIR__ . '/../Views/site/blog.php';
    }

    public function blogPost(string $slug): void
    {
        $post = $this->postRepo->findBySlug($slug);
        $settings = $this->settingsRepo->getAll();

        if (!$post) {
            http_response_code(404);
            $pageTitle = 'Artigo Não Encontrado';
            require __DIR__ . '/../Views/site/404.php';
            return;
        }

        $pageTitle = $post['title'] . ' | Blog Dr. George Scapin';
        $metaDescription = $post['summary'];
        $activePage = 'blog';

        require __DIR__ . '/../Views/site/blog_post.php';
    }

    public function customPage(string $slug): void
    {
        $customPageRepo = new \App\Infrastructure\Repositories\PDOCustomPageRepository();
        $customPage = $customPageRepo->findBySlug($slug);
        $settings = $this->settingsRepo->getAll();

        if (!$customPage) {
            http_response_code(404);
            $pageTitle = 'Página Não Encontrada';
            require __DIR__ . '/../Views/site/404.php';
            return;
        }

        $activePage = 'p_' . $slug;
        require __DIR__ . '/../Views/site/custom_page.php';
    }

    public function contact(): void
    {
        $settings = $this->settingsRepo->getAll();
        $captcha = generate_captcha();
        
        $pageTitle = 'Contato e Agendamento | Dr. George Scapin - Porto Alegre RS';
        $metaDescription = 'Agende sua consulta com o Dr. George Scapin. Clínica na Av. Ipiranga, 40, sala 1512, Porto Alegre.';
        $activePage = 'contact';

        require __DIR__ . '/../Views/site/contact.php';
    }

    public function refreshCaptcha(): void
    {
        $captcha = generate_captcha();
        json_response(['success' => true, 'question' => $captcha['question']]);
    }

    public function submitContact(): void
    {
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || 
                  (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) ||
                  isset($_POST['action']);

        // Proteção Anti-Bot Honeypot (campo oculto que apenas bots preenchem)
        if (!empty($_POST['website']) || !empty($_POST['b_address'])) {
            if ($isAjax) {
                json_response(['success' => true, 'message' => 'Mensagem enviada com sucesso!']);
            }
            flash('success', 'Mensagem enviada com sucesso!');
            redirect('contato');
            return;
        }

        // Validação do Captcha de Segurança Anti-Spam
        $userCaptcha = isset($_POST['captcha']) ? (int)trim($_POST['captcha']) : -1;
        $correctCaptcha = isset($_SESSION['captcha_answer']) ? (int)$_SESSION['captcha_answer'] : -999;

        if ($userCaptcha !== $correctCaptcha) {
            // Gera novo captcha para a próxima tentativa
            $newCaptcha = generate_captcha();
            if ($isAjax) {
                json_response([
                    'success' => false, 
                    'error' => 'Código de verificação (Captcha) incorreto. Por favor, calcule novamente.',
                    'new_captcha' => $newCaptcha['question']
                ], 400);
            }
            flash('error', 'Código de verificação (Captcha) incorreto. Por favor, tente novamente.');
            redirect('contato');
            return;
        }

        // Captcha correto: limpa a resposta da sessão para não ser reutilizada
        unset($_SESSION['captcha_answer']);

        // Proteção contra Spam Flood (Rate Limiter: mínimo 3 segundos entre envios)
        $now = time();
        $lastSent = $_SESSION['last_contact_sent'] ?? 0;
        if (($now - $lastSent) < 3) {
            if ($isAjax) {
                json_response(['success' => false, 'error' => 'Por favor, aguarde alguns segundos antes de enviar outra mensagem.'], 429);
            }
            flash('error', 'Por favor, aguarde alguns segundos antes de enviar outra mensagem.');
            redirect('contato');
            return;
        }

        $nome = trim($_POST['nome'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $mensagem = trim($_POST['mensagem'] ?? '');

        if (empty($nome) || empty($telefone) || empty($mensagem)) {
            if ($isAjax) {
                json_response(['success' => false, 'error' => 'Por favor, preencha todos os campos obrigatórios.'], 400);
            }
            flash('error', 'Por favor, preencha todos os campos obrigatórios.');
            redirect('contato');
            return;
        }

        $success = $this->leadRepo->createContact($nome, $telefone, $mensagem);
        $_SESSION['last_contact_sent'] = time();

        if ($isAjax) {
            json_response(['success' => $success, 'message' => 'Mensagem enviada com sucesso! Em breve entraremos em contato.']);
        }

        flash('success', 'Mensagem enviada com sucesso! Em breve entraremos em contato.');
        redirect('contato');
    }

    public function submitNewsletter(): void
    {
        // Proteção Anti-Bot Honeypot
        if (!empty($_POST['website']) || !empty($_POST['b_address'])) {
            json_response(['success' => true, 'message' => 'E-mail cadastrado com sucesso!']);
            return;
        }

        $email = trim($_POST['email'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(['success' => false, 'error' => 'Por favor, informe um e-mail válido.'], 400);
            return;
        }

        $success = $this->leadRepo->addNewsletterSubscriber($email);
        json_response(['success' => true, 'message' => 'E-mail cadastrado com sucesso!']);
    }

    public function sitemap(): void
    {
        header('Content-Type: application/xml; charset=utf-8');
        
        $baseUrl = 'https://www.drgeorgescapin.com.br';
        if (!empty($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
        }

        $staticPages = [
            ['path' => '/', 'priority' => '1.0', 'freq' => 'weekly'],
            ['path' => '/procedimentos', 'priority' => '0.9', 'freq' => 'weekly'],
            ['path' => '/harmonizacao-facial', 'priority' => '0.9', 'freq' => 'weekly'],
            ['path' => '/clinica', 'priority' => '0.8', 'freq' => 'monthly'],
            ['path' => '/blog', 'priority' => '0.8', 'freq' => 'daily'],
            ['path' => '/contato', 'priority' => '0.8', 'freq' => 'monthly'],
        ];

        $posts = $this->postRepo->getAll(true);
        $customPageRepo = new \App\Infrastructure\Repositories\PDOCustomPageRepository();
        $customPages = $customPageRepo->getAll(true);

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        $today = date('Y-m-d');

        foreach ($staticPages as $page) {
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($baseUrl . $page['path']) . "</loc>\n";
            echo "    <lastmod>" . $today . "</lastmod>\n";
            echo "    <changefreq>" . $page['freq'] . "</changefreq>\n";
            echo "    <priority>" . $page['priority'] . "</priority>\n";
            echo "  </url>\n";
        }

        foreach ($posts as $post) {
            $lastmod = !empty($post['updated_at']) ? date('Y-m-d', strtotime($post['updated_at'])) : $today;
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($baseUrl . '/blog/' . $post['slug']) . "</loc>\n";
            echo "    <lastmod>" . $lastmod . "</lastmod>\n";
            echo "    <changefreq>monthly</changefreq>\n";
            echo "    <priority>0.7</priority>\n";
            echo "  </url>\n";
        }

        foreach ($customPages as $cp) {
            $lastmod = !empty($cp['updated_at']) ? date('Y-m-d', strtotime($cp['updated_at'])) : $today;
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($baseUrl . '/p/' . $cp['slug']) . "</loc>\n";
            echo "    <lastmod>" . $lastmod . "</lastmod>\n";
            echo "    <changefreq>monthly</changefreq>\n";
            echo "    <priority>0.6</priority>\n";
            echo "  </url>\n";
        }

        echo '</urlset>';
        exit;
    }

    public function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        
        $baseUrl = 'https://www.drgeorgescapin.com.br';
        if (!empty($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
        }

        echo "User-agent: *\n";
        echo "Allow: /\n";
        echo "Allow: /assets/\n";
        echo "Allow: /uploads/\n";
        echo "Disallow: /admin/\n";
        echo "Disallow: /backend/\n";
        echo "Disallow: /captcha/\n\n";
        echo "Sitemap: " . $baseUrl . "/sitemap.xml\n";
        exit;
    }
}

