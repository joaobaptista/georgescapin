<?php

namespace App\Middlewares;

use App\Core\Request;
use App\Core\Response;

class RateLimitMiddleware
{
    public function handle(Request $request, string $limitParams = '10,1'): void
    {
        [$maxAttempts, $decayMinutes] = explode(',', $limitParams);
        $maxAttempts = (int) $maxAttempts;
        $decaySeconds = ((int) $decayMinutes) * 60;

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $key = md5($ip . '_' . $request->getPath());
        $cacheDir = sys_get_temp_dir() . '/nuva_rate_limits';

        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0777, true);
        }

        $file = "{$cacheDir}/limit_{$key}.json";
        $now = time();
        $data = ['count' => 0, 'expires_at' => $now + $decaySeconds];

        if (file_exists($file)) {
            $content = @file_get_contents($file);
            $parsed = $content ? json_decode($content, true) : null;
            if ($parsed && $parsed['expires_at'] > $now) {
                $data = $parsed;
            }
        }

        $data['count']++;

        if ($data['count'] > $maxAttempts) {
            $retryAfter = $data['expires_at'] - $now;
            header("Retry-After: {$retryAfter}");
            Response::json(['message' => 'Muitas tentativas. Por favor, aguarde antes de tentar novamente.'], 429);
        }

        @file_put_contents($file, json_encode($data));
    }
}
