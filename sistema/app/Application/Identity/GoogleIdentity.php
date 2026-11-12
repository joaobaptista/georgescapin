<?php

declare(strict_types=1);

namespace App\Application\Identity;

/** Claims of a validated Google ID token. */
final class GoogleIdentity
{
    public function __construct(
        public readonly string $subject,
        public readonly string $email,
        public readonly bool $emailVerified,
        public readonly string $name,
        public readonly ?string $nonce,
    ) {
    }
}
