<?php

declare(strict_types=1);

namespace App\Application\Identity;

/** Port to Google's OpenID Connect (authorization code flow with PKCE). */
interface GoogleIdentityProvider
{
    public function isConfigured(): bool;

    public function authorizationUrl(string $state, string $codeChallenge, string $nonce): string;

    /** Exchanges the authorization code and returns the validated ID token claims. */
    public function exchange(string $code, string $codeVerifier): GoogleIdentity;
}
