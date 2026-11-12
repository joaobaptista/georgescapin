<?php

declare(strict_types=1);

namespace App\Http;

use App\Infrastructure\Config;

/**
 * Client address for rate limiting and audit. X-Forwarded-For is honored only
 * when the direct peer is a trusted proxy (TRUSTED_PROXIES); otherwise anyone
 * could forge it, and behind a proxy every user would share one address.
 */
final class ClientIp
{
    public function __construct(private readonly Config $config)
    {
    }

    public function resolve(array $server): string
    {
        $peer = (string) ($server['REMOTE_ADDR'] ?? '127.0.0.1');

        if ($this->viaTrustedProxy($server) && !empty($server['HTTP_X_FORWARDED_FOR'])) {
            $forwarded = trim(explode(',', (string) $server['HTTP_X_FORWARDED_FOR'])[0]);
            if (filter_var($forwarded, FILTER_VALIDATE_IP)) {
                return $forwarded;
            }
        }

        return $peer;
    }

    /** HTTPS directly, or reported by a trusted proxy that terminates TLS. */
    public function isHttps(array $server): bool
    {
        return ($server['HTTPS'] ?? '') === 'on'
            || ($this->viaTrustedProxy($server) && strtolower((string) ($server['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
    }

    private function viaTrustedProxy(array $server): bool
    {
        $trusted = $this->config->trustedProxies();

        return $trusted === ['*'] || in_array((string) ($server['REMOTE_ADDR'] ?? ''), $trusted, true);
    }
}
