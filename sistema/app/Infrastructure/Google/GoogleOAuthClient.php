<?php

declare(strict_types=1);

namespace App\Infrastructure\Google;

use App\Application\Identity\GoogleIdentity;
use App\Application\Identity\GoogleIdentityProvider;
use App\Domain\Shared\Unauthenticated;
use App\Infrastructure\Config;

/**
 * Google OpenID Connect, authorization code flow with PKCE.
 *
 * The ID token comes straight from Google's token endpoint over TLS, so per
 * OpenID Connect Core §3.1.3.7 the TLS server validation stands in for the
 * signature check; issuer, audience, expiry and nonce are still validated.
 */
final class GoogleOAuthClient implements GoogleIdentityProvider
{
    private const ISSUERS = ['https://accounts.google.com', 'accounts.google.com'];

    public function __construct(private readonly Config $config)
    {
    }

    public function isConfigured(): bool
    {
        return $this->clientId() !== '' && $this->config->get('GOOGLE_CLIENT_SECRET', '') !== '';
    }

    public function authorizationUrl(string $state, string $codeChallenge, string $nonce): string
    {
        return $this->endpoint('GOOGLE_AUTH_ENDPOINT', 'https://accounts.google.com/o/oauth2/v2/auth') . '?' . http_build_query([
            'client_id' => $this->clientId(),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
            'prompt' => 'select_account',
        ]);
    }

    public function exchange(string $code, string $codeVerifier): GoogleIdentity
    {
        $response = $this->postForm($this->endpoint('GOOGLE_TOKEN_ENDPOINT', 'https://oauth2.googleapis.com/token'), [
            'code' => $code,
            'client_id' => $this->clientId(),
            'client_secret' => (string) $this->config->get('GOOGLE_CLIENT_SECRET', ''),
            'redirect_uri' => $this->redirectUri(),
            'grant_type' => 'authorization_code',
            'code_verifier' => $codeVerifier,
        ]);

        $claims = $this->decodeIdToken((string) ($response['id_token'] ?? ''));
        if (!in_array($claims['iss'] ?? null, self::ISSUERS, true)
            || ($claims['aud'] ?? null) !== $this->clientId()
            || (int) ($claims['exp'] ?? 0) < time()
            || empty($claims['sub'])
            || empty($claims['email'])) {
            error_log('[nuva] Google ID token rejected: invalid iss/aud/exp/sub/email');
            throw self::failure();
        }

        return new GoogleIdentity(
            (string) $claims['sub'],
            (string) $claims['email'],
            ($claims['email_verified'] ?? false) === true || ($claims['email_verified'] ?? null) === 'true',
            trim((string) ($claims['name'] ?? '')) ?: strstr((string) $claims['email'], '@', true),
            isset($claims['nonce']) ? (string) $claims['nonce'] : null,
        );
    }

    private function clientId(): string
    {
        return (string) $this->config->get('GOOGLE_CLIENT_ID', '');
    }

    private function redirectUri(): string
    {
        return (string) $this->config->get('GOOGLE_REDIRECT_URI', $this->config->appUrl() . '/api/auth/google/callback');
    }

    /** Endpoint overrides exist for local tests against a fake provider; production always uses Google. */
    private function endpoint(string $key, string $default): string
    {
        return $this->config->isProduction() ? $default : (string) $this->config->get($key, $default);
    }

    private function postForm(string $url, array $fields): array
    {
        $context = stream_context_create(['http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\nAccept: application/json",
            'content' => http_build_query($fields),
            'timeout' => 10,
            'ignore_errors' => true,
        ]]);
        $body = @file_get_contents($url, false, $context);
        $status = isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m) ? (int) $m[1] : 0;
        $data = is_string($body) ? json_decode($body, true) : null;

        if ($status !== 200 || !is_array($data)) {
            error_log(sprintf('[nuva] Google token exchange failed: HTTP %d %s', $status, is_array($data) ? ($data['error'] ?? '') : ''));
            throw self::failure();
        }

        return $data;
    }

    private function decodeIdToken(string $jwt): array
    {
        $parts = explode('.', $jwt);
        $payload = count($parts) === 3 ? base64_decode(strtr($parts[1], '-_', '+/'), true) : false;
        $claims = $payload !== false ? json_decode($payload, true) : null;

        return is_array($claims) ? $claims : throw self::failure();
    }

    private static function failure(): Unauthenticated
    {
        return new Unauthenticated('Não foi possível concluir o login com o Google. Tente novamente.');
    }
}
