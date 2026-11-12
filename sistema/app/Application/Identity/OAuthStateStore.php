<?php

declare(strict_types=1);

namespace App\Application\Identity;

/** Pending sign-ins, keyed by the SHA-256 of the OAuth state. */
interface OAuthStateStore
{
    public function put(string $stateHash, string $codeVerifier, string $nonce, \DateTimeImmutable $createdAt): void;

    /**
     * Returns and deletes the pending sign-in (single use).
     *
     * @return array{codeVerifier: string, nonce: string, createdAt: \DateTimeImmutable}|null
     */
    public function take(string $stateHash): ?array;
}
