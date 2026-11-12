<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Domain\Shared\Clock;
use App\Domain\Shared\RuleViolation;

final class StartGoogleSignIn
{
    public function __construct(
        private readonly GoogleIdentityProvider $google,
        private readonly OAuthStateStore $states,
        private readonly Clock $clock,
    ) {
    }

    /**
     * @return array{url: string, state: string} the state also goes into an
     *         HttpOnly cookie, binding the sign-in to this browser
     */
    public function __invoke(): array
    {
        if (!$this->google->isConfigured()) {
            throw new RuleViolation('O login com o Google não está configurado neste servidor.');
        }

        $state = self::random();
        $verifier = self::random(); // PKCE code verifier
        $nonce = self::random();
        $this->states->put(hash('sha256', $state), $verifier, $nonce, $this->clock->now());
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        return ['url' => $this->google->authorizationUrl($state, $challenge, $nonce), 'state' => $state];
    }

    private static function random(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}
