<?php

declare(strict_types=1);

/**
 * End-to-End HTTP Test Suite for Dr. George Scapin Clinic & CMS.
 *
 * Usage:
 *   APP_TEST_BASE_URL=http://127.0.0.1:8990 php scripts/http-e2e-test.php
 */

const REQUEST_TIMEOUT = 10;

$baseUrl = getenv('APP_TEST_BASE_URL') ?: 'http://127.0.0.1:8990';
$baseUrl = rtrim(trim($baseUrl), '/');

require_once dirname(__DIR__) . '/app/helpers.php';
use App\Infrastructure\Database\Connection;

$passed = 0;
$failed = 0;
$cookieJar = [];

function parseCookies(array $headers): array
{
    global $cookieJar;
    foreach ($headers as $header) {
        if (stripos($header, 'Set-Cookie:') === 0) {
            $cookiePart = trim(substr($header, 11));
            $parts = explode(';', $cookiePart);
            $mainPart = trim($parts[0]);
            if (strpos($mainPart, '=') !== false) {
                list($name, $val) = explode('=', $mainPart, 2);
                $cookieJar[trim($name)] = trim($val);
            }
        }
    }
    return $cookieJar;
}

function buildCookieHeader(): string
{
    global $cookieJar;
    if (empty($cookieJar)) return '';
    $list = [];
    foreach ($cookieJar as $k => $v) {
        $list[] = "{$k}={$v}";
    }
    return 'Cookie: ' . implode('; ', $list);
}

function httpRequest(
    string $url,
    string $method = 'GET',
    $data = null,
    array $extraHeaders = [],
    bool $followRedirects = false
): array {
    global $cookieJar;

    $headers = [
        'User-Agent: GeorgeScapin-E2E-Test/1.0',
        'Connection: close',
    ];

    $cookieHeader = buildCookieHeader();
    if ($cookieHeader) {
        $headers[] = $cookieHeader;
    }

    $content = null;
    if ($data !== null) {
        if (is_array($data)) {
            $content = http_build_query($data);
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        } else {
            $content = (string)$data;
        }
    }

    $headers = array_merge($headers, $extraHeaders);

    $context = stream_context_create([
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'content' => $content,
            'ignore_errors' => true,
            'timeout' => REQUEST_TIMEOUT,
            'follow_location' => $followRedirects ? 1 : 0,
            'protocol_version' => 1.1,
        ]
    ]);

    $body = @file_get_contents($url, false, $context);
    $responseHeaders = $http_response_header ?? [];

    $status = 0;
    foreach ($responseHeaders as $h) {
        if (preg_match('#HTTP/\S+\s+(\d{3})#', $h, $m)) {
            $status = (int)$m[1];
        }
    }

    parseCookies($responseHeaders);

    return [
        'status' => $status,
        'body' => $body ?: '',
        'headers' => $responseHeaders
    ];
}

function hasResponseHeader(array $headers, string $name, ?string $contains = null): bool
{
    foreach ($headers as $h) {
        if (stripos($h, $name . ':') === 0) {
            return $contains === null || stripos($h, $contains) !== false;
        }
    }
    return false;
}

function runHttpTest(string $name, callable $fn): void
{
    global $passed, $failed;
    try {
        $fn();
        $passed++;
        printf("[PASS] %s\n", $name);
    } catch (Throwable $e) {
        $failed++;
        printf("[FAIL] %s: %s\n", $name, $e->getMessage());
    }
}

echo "====================================================\n";
echo "  HTTP E2E TEST SUITE: CLÍNICA DR. GEORGE SCAPIN\n";
echo "  Target URL: {$baseUrl}\n";
echo "====================================================\n\n";

// =========================================================================
// 1. PÁGINAS PÚBLICAS
// =========================================================================
echo "--- 1. Páginas Públicas do Site ---\n";

$publicRoutes = [
    '/' => 'Página Inicial (Home)',
    '/clinica' => 'A Clínica / George Scapin',
    '/procedimentos' => 'Nossos Procedimentos',
    '/harmonizacao-facial' => 'Harmonização Facial Full Face',
    '/blog' => 'Blog de Estética',
    '/contato' => 'Página de Contato & Agendamento',
    '/sitemap.xml' => 'Sitemap XML Dinâmico',
    '/robots.txt' => 'Arquivo Robots.txt Dinâmico',
];

foreach ($publicRoutes as $route => $label) {
    runHttpTest("GET {$route} ({$label})", function() use ($baseUrl, $route) {
        $res = httpRequest($baseUrl . $route);
        if ($res['status'] !== 200) {
            throw new RuntimeException("Esperado HTTP 200, recebido {$res['status']}");
        }
        if ($route === '/sitemap.xml') {
            if (!str_contains($res['body'], '<urlset')) {
                throw new RuntimeException("Sitemap XML não contém a tag <urlset");
            }
        } elseif ($route === '/robots.txt') {
            if (!str_contains($res['body'], 'User-agent')) {
                throw new RuntimeException("Robots.txt não contém diretiva User-agent");
            }
        } else {
            if (!str_contains($res['body'], '<html') || !str_contains($res['body'], 'Dr. George Scapin')) {
                throw new RuntimeException("Página HTML não renderizou o cabeçalho/layout esperado");
            }
        }
    });
}

runHttpTest("GET /blog/{slug} (Post Individual)", function() use ($baseUrl) {
    $pdo = Connection::getInstance();
    $post = $pdo->query("SELECT slug, title FROM posts WHERE is_published = 1 LIMIT 1")->fetch();
    if (!$post) {
        throw new RuntimeException("Nenhum post publicado no banco para teste");
    }
    $res = httpRequest($baseUrl . '/blog/' . $post['slug']);
    if ($res['status'] !== 200) {
        throw new RuntimeException("Esperado HTTP 200 para post existente, recebido {$res['status']}");
    }
    if (!str_contains($res['body'], htmlspecialchars($post['title']))) {
        throw new RuntimeException("Título do post não encontrado no HTML");
    }
});

runHttpTest("GET /blog/{slug_inexistente} retorna 404", function() use ($baseUrl) {
    $res = httpRequest($baseUrl . '/blog/slug-completamente-inexistente-12345');
    if ($res['status'] !== 404) {
        throw new RuntimeException("Esperado HTTP 404 para post inexistente, recebido {$res['status']}");
    }
});

runHttpTest("GET rota desconhecida retorna 404", function() use ($baseUrl) {
    $res = httpRequest($baseUrl . '/pagina-que-nao-existe-xyz');
    if ($res['status'] !== 404) {
        throw new RuntimeException("Esperado HTTP 404, recebido {$res['status']}");
    }
});

// =========================================================================
// 2. CAPTCHA, CONTATO E NEWSLETTER
// =========================================================================
echo "\n--- 2. Formulários, Captcha e Newsletter ---\n";

runHttpTest("GET /captcha/refresh retorna novo desafio aritmético", function() use ($baseUrl) {
    $res = httpRequest($baseUrl . '/captcha/refresh', 'GET', null, ['X-Requested-With: XMLHttpRequest']);
    if ($res['status'] !== 200) {
        throw new RuntimeException("Esperado HTTP 200, recebido {$res['status']}");
    }
    $json = json_decode($res['body'], true);
    if (!is_array($json) || empty($json['success']) || empty($json['question'])) {
        throw new RuntimeException("Resposta JSON do captcha inválida: " . $res['body']);
    }
});

runHttpTest("POST /newsletter/assinar com e-mail inválido retorna 400", function() use ($baseUrl) {
    $res = httpRequest($baseUrl . '/newsletter/assinar', 'POST', ['email' => 'email_invalido'], ['X-Requested-With: XMLHttpRequest']);
    if ($res['status'] !== 400) {
        throw new RuntimeException("Esperado HTTP 400 para e-mail inválido, recebido {$res['status']}");
    }
    $json = json_decode($res['body'], true);
    if (!empty($json['success'])) {
        throw new RuntimeException("Não deveria ter sucesso com e-mail inválido");
    }
});

runHttpTest("POST /newsletter/assinar com e-mail válido cadastra assinante", function() use ($baseUrl) {
    $email = 'test_http_' . time() . '@example.com';
    $res = httpRequest($baseUrl . '/newsletter/assinar', 'POST', ['email' => $email], ['X-Requested-With: XMLHttpRequest']);
    if ($res['status'] !== 200) {
        throw new RuntimeException("Esperado HTTP 200, recebido {$res['status']}");
    }
    $json = json_decode($res['body'], true);
    if (empty($json['success'])) {
        throw new RuntimeException("Esperado success=true no cadastro de newsletter");
    }

    // Cleanup
    $pdo = Connection::getInstance();
    $pdo->prepare("DELETE FROM newsletter_subscribers WHERE email = ?")->execute([$email]);
});

runHttpTest("POST /contato/enviar honeypot bot trap", function() use ($baseUrl) {
    $res = httpRequest($baseUrl . '/contato/enviar', 'POST', [
        'website' => 'http://spam-bot.example.com',
        'nome' => 'Spam Bot',
        'telefone' => '11999999999',
        'mensagem' => 'Spam text'
    ], ['X-Requested-With: XMLHttpRequest']);
    
    // Honeypot responde fake success para despistar bots
    $json = json_decode($res['body'], true);
    if (empty($json['success'])) {
        throw new RuntimeException("Honeypot deveria retornar resposta amigável sem gravar");
    }

    // Garante que não gravou no banco
    $pdo = Connection::getInstance();
    $lead = $pdo->query("SELECT id FROM contact_messages WHERE nome = 'Spam Bot'")->fetch();
    if ($lead) {
        throw new RuntimeException("Spam Bot não deveria ter sido persistido no banco");
    }
});

runHttpTest("POST /contato/enviar valida captcha incorreto", function() use ($baseUrl) {
    $res = httpRequest($baseUrl . '/contato/enviar', 'POST', [
        'nome' => 'Lead Teste',
        'telefone' => '(51) 99999-0000',
        'mensagem' => 'Olá Dr. George',
        'captcha' => '9999' // Resposta errada
    ], ['X-Requested-With: XMLHttpRequest']);

    if ($res['status'] !== 400) {
        throw new RuntimeException("Esperado HTTP 400 para captcha incorreto, recebido {$res['status']}");
    }
    $json = json_decode($res['body'], true);
    if (!empty($json['success'])) {
        throw new RuntimeException("Envio com captcha errado não pode ter sucesso");
    }
});

// =========================================================================
// 3. SEGURANÇA E AUTENTICAÇÃO ADMINISTRATIVA
// =========================================================================
echo "\n--- 3. Segurança e Painel Administrativo ---\n";

runHttpTest("Acesso anônimo a /admin redireciona para login", function() use ($baseUrl) {
    global $cookieJar;
    $cookieJar = []; // Limpa cookies

    $res = httpRequest($baseUrl . '/admin');
    if (!in_array($res['status'], [302, 301, 303, 307])) {
        throw new RuntimeException("Esperado redirecionamento (302) para /admin/login, recebido HTTP {$res['status']}");
    }
    if (!hasResponseHeader($res['headers'], 'Location', 'admin/login')) {
        throw new RuntimeException("Header Location não aponta para admin/login");
    }
});

runHttpTest("GET /admin/login carrega formulário de autenticação com CSRF", function() use ($baseUrl) {
    $res = httpRequest($baseUrl . '/admin/login');
    if ($res['status'] !== 200) {
        throw new RuntimeException("Esperado HTTP 200 em /admin/login, recebido {$res['status']}");
    }
    if (!str_contains($res['body'], 'name="csrf_token"') || !str_contains($res['body'], 'Painel')) {
        throw new RuntimeException("Formulário de login não contém campo csrf_token ou elementos de UI");
    }
});

runHttpTest("POST /admin/login sem CSRF válido é rejeitado", function() use ($baseUrl) {
    $res = httpRequest($baseUrl . '/admin/login', 'POST', [
        'email' => 'admin@drgeorgescapin.com.br',
        'password' => 'qualquer',
        'csrf_token' => 'token_forjado'
    ]);
    if (!in_array($res['status'], [302, 303])) {
        throw new RuntimeException("Esperado redirect após falha de CSRF, recebido {$res['status']}");
    }
});

runHttpTest("POST /admin/login com credenciais inválidas falha", function() use ($baseUrl) {
    // 1. Abre a página de login para pegar o csrf_token gerado na sessão
    $loginPage = httpRequest($baseUrl . '/admin/login');
    preg_match('/name="csrf_token" value="([^"]+)"/', $loginPage['body'], $m);
    if (empty($m[1])) {
        throw new RuntimeException("Não foi possível extrair csrf_token da página de login");
    }
    $csrfToken = $m[1];

    $res = httpRequest($baseUrl . '/admin/login', 'POST', [
        'email' => 'admin@drgeorgescapin.com.br',
        'password' => 'senha_totalmente_errada_123',
        'csrf_token' => $csrfToken
    ]);
    if (!in_array($res['status'], [302, 303])) {
        throw new RuntimeException("Esperado redirect para /admin/login após senha incorreta");
    }
});

runHttpTest("POST /admin/login com credenciais válidas autentica com sucesso", function() use ($baseUrl) {
    $pdo = Connection::getInstance();
    $user = $pdo->query("SELECT id, email, password FROM users LIMIT 1")->fetch();
    if (!$user) {
        throw new RuntimeException("Nenhum usuário no banco para autenticação");
    }

    // Atualiza a senha temporariamente para teste se necessário ou usa senha padrão
    $testPassword = 'AdminSecretPassword' . time() . '!';
    $newHash = password_hash($testPassword, PASSWORD_BCRYPT);
    $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$newHash, $user['id']]);

    // Obtém CSRF
    $loginPage = httpRequest($baseUrl . '/admin/login');
    preg_match('/name="csrf_token" value="([^"]+)"/', $loginPage['body'], $m);
    $csrfToken = $m[1] ?? '';

    $res = httpRequest($baseUrl . '/admin/login', 'POST', [
        'email' => $user['email'],
        'password' => $testPassword,
        'csrf_token' => $csrfToken
    ]);

    if (!in_array($res['status'], [302, 303])) {
        throw new RuntimeException("Esperado redirect 302 para /admin após login bem-sucedido, recebido {$res['status']}");
    }

    // Restaura hash original
    $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$user['password'], $user['id']]);
});

// =========================================================================
// 4. ÁREA ADMINISTRATIVA AUTENTICADA
// =========================================================================
echo "\n--- 4. Rotas e Operações Administrativas (Autenticado) ---\n";

$adminPages = [
    '/admin' => 'Dashboard',
    '/admin/dashboard' => 'Dashboard principal',
    '/admin/procedures' => 'Listagem de Tratamentos',
    '/admin/procedures/create' => 'Formulário Novo Tratamento',
    '/admin/posts' => 'Listagem de Posts do Blog',
    '/admin/posts/create' => 'Formulário Novo Post',
    '/admin/pages' => 'Gerenciamento de Páginas',
    '/admin/pages/home' => 'Edição Página Inicial',
    '/admin/pages/clinic' => 'Edição Página Clínica',
    '/admin/pages/harmonization' => 'Edição Harmonização',
    '/admin/custom-pages' => 'Listagem Páginas Customizadas',
    '/admin/custom-pages/create' => 'Formulário Nova Página Customizada',
    '/admin/menu' => 'Gerenciamento do Menu de Navegação',
    '/admin/leads' => 'Painel de Leads e Contatos',
    '/admin/leads/export' => 'Exportação CSV de Leads',
    '/admin/newsletter/export' => 'Exportação CSV de Newsletter',
    '/admin/settings' => 'Configurações Gerais do Site',
    '/admin/profile' => 'Perfil e Senha do Administrador',
    '/admin/design-system' => 'Design System e Guia UI',
];

foreach ($adminPages as $route => $label) {
    runHttpTest("GET {$route} ({$label})", function() use ($baseUrl, $route) {
        $res = httpRequest($baseUrl . $route);
        if ($res['status'] !== 200) {
            throw new RuntimeException("Esperado HTTP 200, recebido {$res['status']}");
        }
        if (str_contains($route, '/export')) {
            if (!hasResponseHeader($res['headers'], 'Content-Type', 'text/csv')) {
                throw new RuntimeException("Exportação deve retornar Content-Type text/csv");
            }
        } else {
            if (!str_contains($res['body'], 'Dr. George Scapin') && !str_contains($res['body'], 'Painel')) {
                throw new RuntimeException("Layout administrativo não foi renderizado");
            }
        }
    });
}

runHttpTest("GET /admin/logout encerra a sessão", function() use ($baseUrl) {
    $res = httpRequest($baseUrl . '/admin/logout');
    if (!in_array($res['status'], [302, 303])) {
        throw new RuntimeException("Esperado redirect após logout, recebido {$res['status']}");
    }

    // Tentativa subsequente a /admin deve redirecionar para login
    $adminTry = httpRequest($baseUrl . '/admin');
    if (!in_array($adminTry['status'], [302, 303])) {
        throw new RuntimeException("Após logout, /admin não deveria ser acessível sem login");
    }
});

echo "\n====================================================\n";
printf("Resultado E2E: %d/%d passaram", $passed, $passed + $failed);
if ($failed > 0) {
    printf(", %d falharam.\n", $failed);
    exit(1);
}
echo ".\n====================================================\n";
