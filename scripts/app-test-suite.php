<?php

declare(strict_types=1);

/**
 * Test Suite Completa para o Sistema Principal (Dr. George Scapin CMS & Site).
 * Executa testes unitários, de repositório, de segurança e E2E via HTTP.
 *
 * Uso: php scripts/app-test-suite.php
 */

$root = dirname(__DIR__);
require_once $root . '/app/helpers.php';

use App\Infrastructure\Database\Connection;
use App\Infrastructure\Repositories\PDOProcedureRepository;
use App\Infrastructure\Repositories\PDOPostRepository;
use App\Infrastructure\Repositories\PDOCustomPageRepository;
use App\Infrastructure\Repositories\PDOContentRepository;
use App\Infrastructure\Repositories\PDOSettingsRepository;
use App\Infrastructure\Repositories\PDOMenuRepository;
use App\Infrastructure\Repositories\PDOLeadRepository;
use App\Infrastructure\Repositories\PDOUserRepository;
use App\Infrastructure\Services\ImageOptimizer;

$passed = 0;
$failed = 0;

function assertCondition(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function runTestCase(string $section, string $name, callable $test): void
{
    global $passed, $failed;

    try {
        $test();
        $passed++;
        printf("[PASS] %s -> %s\n", $section, $name);
    } catch (Throwable $e) {
        $failed++;
        printf("[FAIL] %s -> %s: %s\n", $section, $name, $e->getMessage());
    }
}

echo "====================================================\n";
echo "  SUÍTE DE TESTES: CLÍNICA DR. GEORGE SCAPIN\n";
echo "====================================================\n\n";

// =========================================================================
// 1. TESTES UNITÁRIOS DE HELPERS
// =========================================================================
echo "--- 1. Helpers e Utilitários ---\n";

runTestCase('Helpers', 'slugify converte acentos, espaços e caracteres especiais', function() {
    assertCondition(slugify('Harmonização Facial & Botox 2026!') === 'harmonizacao-facial-botox-2026', 'Falha no slugify com acentos e símbolos');
    assertCondition(slugify('   Espaços   Múltiplos   ') === 'espacos-multiplos', 'Falha no slugify com múltiplos espaços');
    assertCondition(slugify('!!!') === 'n-a', 'Falha no slugify com string vazia resultante');
});

runTestCase('Helpers', 'generate_captcha gera pergunta e resposta coerente', function() {
    $captcha = generate_captcha();
    assertCondition(isset($captcha['question'], $captcha['n1'], $captcha['n2']), 'Chaves do captcha ausentes');
    assertCondition($_SESSION['captcha_answer'] === ($captcha['n1'] + $captcha['n2']), 'Resposta da sessão não bate com a soma');
});

runTestCase('Helpers', 'CSRF token geração e validação', function() {
    unset($_SESSION['csrf_token']);
    $token1 = csrf_token();
    assertCondition(!empty($token1) && strlen($token1) === 64, 'Token CSRF deve ser hex de 32 bytes (64 chars)');
    
    $_POST['csrf_token'] = $token1;
    assertCondition(verify_csrf() === true, 'verify_csrf deve validar token idêntico via POST');
    
    $_POST['csrf_token'] = 'token_invalido';
    assertCondition(verify_csrf() === false, 'verify_csrf deve rejeitar token incorreto');
    unset($_POST['csrf_token']);
});

runTestCase('Helpers', 'flash messages ciclo de vida', function() {
    flash('info', 'Mensagem de teste');
    assertCondition(flash('info') === 'Mensagem de teste', 'Mensagem flash não retornou o valor esperado');
    assertCondition(flash('info') === null, 'Mensagem flash deve ser consumida e retornar null na 2a chamada');
});

runTestCase('Helpers', 'sanitize protege contra XSS', function() {
    $raw = '<script>alert("xss")</script>';
    $sanitized = sanitize($raw);
    assertCondition($sanitized === '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', 'sanitize deve converter tags HTML');
});

runTestCase('Helpers', 'render_pagination gera links corretos', function() {
    $html = render_pagination(2, 5, '/blog');
    assertCondition(strpos($html, 'Anterior') !== false, 'Paginação na página 2 deve ter link Anterior');
    assertCondition(strpos($html, 'Próximo') !== false, 'Paginação na página 2 de 5 deve ter link Próximo');
    assertCondition(render_pagination(1, 1, '/blog') === '', 'Paginação de 1 página deve retornar string vazia');
});

// =========================================================================
// 2. TESTES DO IMAGE OPTIMIZER
// =========================================================================
echo "\n--- 2. Image Optimizer (GD + WebP) ---\n";

runTestCase('ImageOptimizer', 'Converte imagem PNG gerada para WebP', function() {
    $tmpDir = sys_get_temp_dir() . '/george_test_img';
    if (!is_dir($tmpDir)) mkdir($tmpDir, 0777, true);

    $srcPng = $tmpDir . '/source.png';
    $destWebp = $tmpDir . '/dest.webp';

    // Cria uma imagem PNG de teste 200x200
    $img = imagecreatetruecolor(200, 200);
    $gold = imagecolorallocate($img, 197, 160, 89);
    imagefill($img, 0, 0, $gold);
    imagepng($img, $srcPng);
    imagedestroy($img);

    $success = ImageOptimizer::processAndConvertToWebp($srcPng, $destWebp, 100, 100, 80);
    assertCondition($success === true, 'processAndConvertToWebp deve retornar true');
    assertCondition(file_exists($destWebp), 'Arquivo WebP deve existir no destino');
    
    $info = getimagesize($destWebp);
    assertCondition($info[2] === IMAGETYPE_WEBP, 'Arquivo gerado deve ser do tipo IMAGETYPE_WEBP');
    assertCondition($info[0] <= 100 && $info[1] <= 100, 'Dimensões do WebP devem respeitar o limite');

    @unlink($srcPng);
    @unlink($destWebp);
    @rmdir($tmpDir);
});

// =========================================================================
// 3. TESTES DE INTEGRAÇÃO DOS REPOSITÓRIOS (MYSQL)
// =========================================================================
echo "\n--- 3. Repositórios MySQL ---\n";

runTestCase('PDOUserRepository', 'Busca usuário existente e valida hash de senha', function() {
    $repo = new PDOUserRepository();
    $user = $repo->findById(1);
    assertCondition($user !== null, 'Usuário ID 1 não encontrado');
    assertCondition(!empty($user['email']), 'Email do usuário não pode ser vazio');

    $userWithPass = $repo->findWithPasswordById(1);
    assertCondition(isset($userWithPass['password']), 'findWithPasswordById deve conter o hash da senha');
});

runTestCase('PDOProcedureRepository', 'CRUD de Procedimentos', function() {
    $repo = new PDOProcedureRepository();
    
    $procId = $repo->create([
        'title' => 'Procedimento Teste ' . time(),
        'slug' => 'proc-teste-' . time(),
        'short_description' => 'Descrição curta do procedimento teste',
        'full_description' => '<p>Descrição completa detalhada</p>',
        'icon_name' => 'sparkles',
        'image_url' => '/assets/images/botox.png',
        'sort_order' => 99,
        'is_active' => 1
    ]);
    assertCondition($procId > 0, 'ID do procedimento inserido deve ser > 0');

    $proc = $repo->findById($procId);
    assertCondition($proc !== null, 'Procedimento criado não foi encontrado por ID');
    assertCondition($proc['sort_order'] == 99, 'sort_order não confere');

    $updated = $repo->update($procId, [
        'title' => 'Procedimento Atualizado',
        'slug' => 'proc-atualizado-' . time(),
        'short_description' => 'Nova descrição',
        'full_description' => '<p>Nova desc completa</p>',
        'icon_name' => 'star',
        'image_url' => '',
        'sort_order' => 100,
        'is_active' => 0
    ]);
    assertCondition($updated === true, 'Update do procedimento deve retornar true');

    $procUpdated = $repo->findById($procId);
    assertCondition($procUpdated['title'] === 'Procedimento Atualizado', 'Título atualizado não confere');
    assertCondition($procUpdated['is_active'] == 0, 'is_active atualizado não confere');

    $deleted = $repo->delete($procId);
    assertCondition($deleted === true, 'Delete do procedimento deve retornar true');
    assertCondition($repo->findById($procId) === null, 'Procedimento deletado ainda foi encontrado');
});

runTestCase('PDOPostRepository', 'CRUD e Paginação de Posts do Blog', function() {
    $repo = new PDOPostRepository();
    
    $slug = 'post-teste-' . time();
    $postId = $repo->create([
        'title' => 'Post de Teste Automatizado',
        'slug' => $slug,
        'author' => 'Dr. George',
        'summary' => 'Resumo do artigo de teste para o blog',
        'content' => '<p>Conteúdo rico do post de teste.</p>',
        'image_url' => '',
        'is_published' => 1
    ]);
    assertCondition($postId > 0, 'Post ID deve ser > 0');

    $post = $repo->findBySlug($slug);
    assertCondition($post !== null, 'Post criado deve ser encontrado por slug');
    assertCondition($post['id'] == $postId, 'ID do post por slug não confere');

    $pag = $repo->getPaginated(1, 10, true);
    assertCondition($pag['total'] >= 1, 'Total de posts paginados deve ser >= 1');
    assertCondition(count($pag['data']) >= 1, 'Data de posts paginados deve conter itens');

    $repo->delete($postId);
    assertCondition($repo->findById($postId) === null, 'Post deletado não deve existir');
});

runTestCase('PDOCustomPageRepository', 'CRUD de Páginas Customizadas', function() {
    $repo = new PDOCustomPageRepository();
    
    $slug = 'pagina-teste-' . time();
    $pageId = $repo->create([
        'title' => 'Página Custom Teste',
        'slug' => $slug,
        'subtitle' => 'Subtítulo da página custom',
        'content' => '<h2>Conteúdo da Página Custom</h2><p>Texto explicativo.</p>',
        'banner_image' => '',
        'meta_description' => 'Meta description custom',
        'is_published' => 1
    ]);
    assertCondition($pageId > 0, 'Custom page ID deve ser > 0');

    $customPage = $repo->findBySlug($slug);
    assertCondition($customPage !== null, 'Página custom não encontrada por slug');
    assertCondition($customPage['title'] === 'Página Custom Teste', 'Título não confere');

    $repo->delete($pageId);
    assertCondition($repo->findById($pageId) === null, 'Página deletada ainda existe');
});

runTestCase('PDOLeadRepository', 'Contatos, Newsletter e Métricas de Leads', function() {
    $repo = new PDOLeadRepository();

    // Contato
    $nome = 'Lead Teste ' . time();
    $tel = '(51) 99999-8888';
    $msg = 'Mensagem de teste automatizado de lead';
    $created = $repo->createContact($nome, $tel, $msg);
    assertCondition($created === true, 'createContact deve retornar true');

    $contacts = $repo->getAllContacts();
    $foundLead = null;
    foreach ($contacts as $c) {
        if ($c['nome'] === $nome) {
            $foundLead = $c;
            break;
        }
    }
    assertCondition($foundLead !== null, 'Lead recém-criado não foi listado');
    assertCondition($foundLead['status'] === 'novo', 'Status padrão do lead deve ser novo');

    $updatedStatus = $repo->updateContactStatus((int)$foundLead['id'], 'contatado');
    assertCondition($updatedStatus === true, 'updateContactStatus deve retornar true');

    $repo->deleteContact((int)$foundLead['id']);

    // Newsletter
    $testEmail = 'news_' . time() . '@example.test';
    $addedNews = $repo->addNewsletterSubscriber($testEmail);
    assertCondition($addedNews === true, 'addNewsletterSubscriber deve retornar true');

    $subscribers = $repo->getAllNewsletterSubscribers();
    $foundSub = null;
    foreach ($subscribers as $s) {
        if ($s['email'] === $testEmail) {
            $foundSub = $s;
            break;
        }
    }
    assertCondition($foundSub !== null, 'Assinante de newsletter não encontrado');
    $repo->deleteNewsletterSubscriber((int)$foundSub['id']);
});

runTestCase('PDOSettingsRepository', 'Leitura e escrita de configurações do site', function() {
    $repo = new PDOSettingsRepository();
    
    $origTitle = $repo->get('site_title');
    $testTitle = 'Dr. George Scapin - Teste ' . time();
    
    $repo->set('site_title', $testTitle);
    assertCondition($repo->get('site_title') === $testTitle, 'Configuração site_title não foi atualizada');

    // Restaura
    if ($origTitle !== null) {
        $repo->set('site_title', $origTitle);
    }
});

runTestCase('PDOMenuRepository', 'Gerenciamento dos itens de menu de navegação', function() {
    $repo = new PDOMenuRepository();
    
    $menuId = $repo->create([
        'label' => 'Menu Teste ' . time(),
        'url' => '/teste',
        'target' => '_self',
        'sort_order' => 99,
        'is_active' => 1,
        'is_button' => 0
    ]);
    assertCondition($menuId > 0, 'Menu item ID deve ser > 0');

    $item = $repo->findById($menuId);
    assertCondition($item !== null && $item['url'] === '/teste', 'Menu item não confere');

    $repo->delete($menuId);
    assertCondition($repo->findById($menuId) === null, 'Menu deletado ainda encontrado');
});

runTestCase('PDOContentRepository', 'Conteúdo da Home (Hero) e Clínica', function() {
    $repo = new PDOContentRepository();
    
    $hero = $repo->getHeroContent();
    assertCondition(!empty($hero['title']), 'Hero title não pode ser vazio');
    assertCondition(!empty($hero['button_link']), 'Hero button link não pode ser vazio');

    $clinic = $repo->getClinicContent();
    assertCondition(!empty($clinic['title']), 'Clinic title não pode ser vazio');
    assertCondition(!empty($clinic['paragraph_1']), 'Clinic paragraph_1 não pode ser vazio');
});

echo "\n====================================================\n";
printf("Resultado: %d/%d passaram", $passed, $passed + $failed);
if ($failed > 0) {
    printf(", %d falharam.\n", $failed);
    exit(1);
}
echo ".\n====================================================\n";
