<?php

declare(strict_types=1);

/**
 * Static readiness check for the pure PHP + native frontend deployment.
 *
 * Usage: php scripts/deploy-check.php
 */

$root = dirname(__DIR__);
$requiredFiles = [
    'public/index.php',
    'public/index.html',
    'public/assets/css/app.css',
    'public/assets/js/app.js',
    'public/.htaccess',
    '.htaccess',
    'composer.json',
    'routes/api.php',
];
$removedLegacyTargets = [
    'package.json',
    'package-lock.json',
    'vite.config.js',
    'node_modules',
    'resources',
    'public/build',
    'public/hot',
];

$passed = 0;
$failed = 0;

/** @param bool $condition */
function check(string $name, bool $condition, string $detail): void
{
    global $passed, $failed;

    printf("%s %s — %s\n", $condition ? 'PASS' : 'FAIL', $name, $detail);
    if ($condition) {
        $passed++;
        return;
    }

    $failed++;
}

echo "Nüva deployment readiness check\n\n";

foreach ($requiredFiles as $file) {
    check("Required {$file}", is_file($root . '/' . $file), is_file($root . '/' . $file) ? 'present' : 'missing');
}

foreach ($removedLegacyTargets as $target) {
    $exists = file_exists($root . '/' . $target);
    check("Legacy target removed: {$target}", !$exists, $exists ? 'still present' : 'absent');
}

$htmlPath = $root . '/public/index.html';
$html = is_file($htmlPath) ? (string) file_get_contents($htmlPath) : '';
check(
    'Native frontend asset references',
    str_contains($html, '/assets/css/app.css') && str_contains($html, '/assets/js/app.js'),
    'index.html references local CSS and JavaScript assets'
);
check(
    'No legacy frontend runtime in HTML',
    preg_match('/(?:localhost:5173|@vite|resources\/js\/app\.js)/i', $html) !== 1,
    'no Vite development/build entry point found'
);

$composerPath = $root . '/composer.json';
$composer = is_file($composerPath) ? json_decode((string) file_get_contents($composerPath), true) : null;
$composerPackages = is_array($composer) ? array_keys($composer['require'] ?? []) : [];
$hasLaravelPackage = array_filter(
    $composerPackages,
    static fn (string $package): bool => str_starts_with($package, 'laravel/') || str_starts_with($package, 'illuminate/')
);
check(
    'Composer has no Laravel runtime package',
    is_array($composer) && $hasLaravelPackage === [],
    is_array($composer) && $hasLaravelPackage === [] ? 'clean' : 'composer.json is invalid or still requires Laravel'
);

$total = $passed + $failed;
echo "\nResult: {$passed}/{$total} passed";
if ($failed > 0) {
    echo ", {$failed} failed.\n";
    exit(1);
}

echo ".\n";
