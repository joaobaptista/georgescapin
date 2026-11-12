<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\Support\TestCase;

/** Clean Architecture: dependencies point inwards only. */
final class ArchitectureTest extends TestCase
{
    private const RULES = [
        'Domain' => ['App\\Application', 'App\\Infrastructure', 'App\\Http', 'PDO', 'Dotenv'],
        'Application' => ['App\\Infrastructure', 'App\\Http', 'PDO', 'Dotenv'],
        'Infrastructure' => ['App\\Http'],
    ];

    public function testLayersDependOnlyInwards(): void
    {
        $violations = [];
        foreach (self::RULES as $layer => $forbidden) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(dirname(__DIR__, 2) . "/app/{$layer}"));
            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }
                $code = (string) file_get_contents($file->getPathname());
                foreach ($forbidden as $dependency) {
                    if (preg_match('/^use\s+' . preg_quote($dependency, '/') . '\b/m', $code) || preg_match('/new\s+\\\\?' . preg_quote($dependency, '/') . '\b/', $code)) {
                        $violations[] = "{$layer}/" . $file->getFilename() . " → {$dependency}";
                    }
                }
            }
        }
        self::assertSame([], $violations, 'dependências proibidas:');
    }
}
