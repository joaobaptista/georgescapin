<?php

// Carregador automático de variáveis de ambiente do arquivo .env
(function() {
    $envFile = __DIR__ . '/../.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || $line[0] === '#') continue;
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                // Remove aspas simples ou duplas envolventes
                $value = trim($value, "\"'");
                putenv("{$name}={$value}");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
})();

// Autoloader PSR-4 para o namespace App\
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});


if (!function_exists('config')) {
    function config(string $key, $default = null) {
        static $configs = [];
        $parts = explode('.', $key);
        $file = $parts[0];
        $item = $parts[1] ?? null;

        if (!isset($configs[$file])) {
            $filePath = __DIR__ . '/../config/' . $file . '.php';
            if (file_exists($filePath)) {
                $configs[$file] = require $filePath;
            } else {
                $configs[$file] = [];
            }
        }

        if ($item === null) {
            return $configs[$file];
        }

        return $configs[$file][$item] ?? $default;
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string {
        $path = ltrim($path, '/');
        return '/' . $path;
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string {
        $path = ltrim($path, '/');
        return '/' . $path;
    }
}

if (!function_exists('admin_view_path')) {
    function admin_view_path(string $view): string {
        $view = ltrim($view, '/');
        $rootAdmin = dirname(__DIR__) . '/admin/views/' . $view . '.php';
        if (file_exists($rootAdmin)) {
            return $rootAdmin;
        }
        $rootAdminDirect = dirname(__DIR__) . '/admin/' . $view . '.php';
        if (file_exists($rootAdminDirect)) {
            return $rootAdminDirect;
        }
        return dirname(__DIR__) . '/app/Presentation/Views/admin/' . $view . '.php';
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
    }
}

if (!function_exists('verify_csrf')) {
    function verify_csrf(): bool {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
}

if (!function_exists('sanitize')) {
    function sanitize(string $data): string {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('flash')) {
    function flash(string $key, ?string $message = null) {
        if ($message !== null) {
            $_SESSION['flash'][$key] = $message;
            return;
        }
        if (isset($_SESSION['flash'][$key])) {
            $msg = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $msg;
        }
        return null;
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path) {
        header("Location: " . url($path));
        exit;
    }
}

if (!function_exists('json_response')) {
    function json_response(array $data, int $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}

if (!function_exists('render_pagination')) {
    function render_pagination(int $currentPage, int $totalPages, string $baseUrl, string $paramName = 'page'): string {
        if ($totalPages <= 1) return '';

        $parseUrl = parse_url($baseUrl);
        $path = $parseUrl['path'] ?? '/';
        $queryParams = [];
        if (!empty($parseUrl['query'])) {
            parse_str($parseUrl['query'], $queryParams);
        }
        // Merge with existing GET params except current pagination param
        foreach ($_GET as $k => $v) {
            if ($k !== $paramName) {
                $queryParams[$k] = $v;
            }
        }

        $buildUrl = function(int $page) use ($path, $queryParams, $paramName) {
            $params = array_merge($queryParams, [$paramName => $page]);
            return $path . '?' . http_build_query($params);
        };

        $html = '<nav aria-label="Paginação" style="display:flex;align-items:center;justify-content:center;gap:6px;margin-top:auto;padding:18px 20px;">';

        if ($currentPage > 1) {
            $html .= '<a href="' . htmlspecialchars($buildUrl($currentPage - 1)) . '" class="btn-admin btn-secondary" style="padding:6px 12px;font-size:0.82rem;display:inline-flex;align-items:center;gap:4px;"><i data-lucide="chevron-left" size="14"></i> Anterior</a>';
        }

        for ($i = 1; $i <= $totalPages; $i++) {
            if ($i === $currentPage) {
                $html .= '<span style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;background:var(--color-primary);color:#fff;font-weight:600;border-radius:var(--radius-sm);font-size:0.84rem;">' . $i . '</span>';
            } else {
                $html .= '<a href="' . htmlspecialchars($buildUrl($i)) . '" style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border:1px solid var(--border-light);background:var(--bg-surface);color:var(--text-primary);text-decoration:none;border-radius:var(--radius-sm);font-size:0.84rem;transition:all 0.15s ease;">' . $i . '</a>';
            }
        }

        if ($currentPage < $totalPages) {
            $html .= '<a href="' . htmlspecialchars($buildUrl($currentPage + 1)) . '" class="btn-admin btn-secondary" style="padding:6px 12px;font-size:0.82rem;display:inline-flex;align-items:center;gap:4px;">Próximo <i data-lucide="chevron-right" size="14"></i></a>';
        }

        $html .= '</nav>';
        return $html;
    }
}

if (!function_exists('slugify')) {
    function slugify(string $text): string {
        // Remove acentos
        $transliterator = \transliterator_create('Any-Latin; Latin-ASCII; Lower()');
        if ($transliterator) {
            $text = $transliterator->transliterate($text);
        } else {
            $text = @iconv('UTF-8', 'ASCII//TRANSLIT', $text) ?: $text;
            $text = strtolower($text);
        }
        
        // Remove caracteres que não sejam letras, números ou hífens
        $text = preg_replace('~[^\\pL\\d]+~u', '-', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);

        return empty($text) ? 'n-a' : $text;
    }
}

if (!function_exists('generate_captcha')) {
    function generate_captcha(): array {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        $n1 = rand(2, 9);
        $n2 = rand(1, 9);
        $_SESSION['captcha_answer'] = $n1 + $n2;
        return [
            'question' => "Quanto é {$n1} + {$n2}?",
            'n1' => $n1,
            'n2' => $n2
        ];
    }
}

