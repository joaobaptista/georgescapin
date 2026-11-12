<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Domain\Shared\Clock;
use App\Domain\Shared\Unauthenticated;

/** Turns a one-time login code into a session (POST, never in a URL). */
final class ExchangeLoginCode
{
    private const TTL_SECONDS = 60;

    public function __construct(
        private readonly LoginCodeStore $codes,
        private readonly SessionIssuer $sessions,
        private readonly Clock $clock,
    ) {
    }

    /** @return array{token: string, userId: int} */
    public function __invoke(string $code): array
    {
        $entry = $this->codes->take(hash('sha256', $code));
        if ($entry === null || $this->clock->now()->getTimestamp() - $entry['createdAt']->getTimestamp() > self::TTL_SECONDS) {
            throw new Unauthenticated('Código de login inválido ou expirado. Entre novamente.');
        }

        return ['token' => $this->sessions->issue($entry['userId']), 'userId' => $entry['userId']];
    }
}
