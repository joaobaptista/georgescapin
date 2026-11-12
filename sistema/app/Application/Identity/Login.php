<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Shared\RateLimiter;
use App\Domain\Audit\AuditTrail;
use App\Domain\Identity\PasswordHasher;
use App\Domain\Identity\UserRepository;
use App\Domain\Shared\TooManyAttempts;
use App\Domain\Shared\Unauthenticated;

final class Login
{
    /** Failed attempts per account, on top of the per-IP limit of the route. */
    private const MAX_FAILURES = 10;
    private const FAILURE_WINDOW_SECONDS = 900;

    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
        private readonly SessionIssuer $sessions,
        private readonly RateLimiter $limiter,
        private readonly AuditTrail $audit,
    ) {
    }

    /** @return array{token: string, userId: int} */
    public function __invoke(string $email, string $password, ?string $ip): array
    {
        $key = 'login-account:' . hash('sha256', mb_strtolower(trim($email)));
        if (($retry = $this->limiter->blockedFor($key, self::MAX_FAILURES)) !== null) {
            throw new TooManyAttempts($retry);
        }

        $user = $this->users->findByEmail($email);
        if ($user === null) {
            // Same bcrypt cost as a real check: the response time does not
            // reveal whether the e-mail has an account.
            $this->hasher->hash($password);
        }
        $valid = $user !== null && $this->hasher->verify($password, $user->passwordHash);

        if (!$valid) {
            $this->limiter->hit($key, self::MAX_FAILURES, self::FAILURE_WINDOW_SECONDS);
            $this->audit->record('login.failed', $user?->id, ip: $ip);
            throw new Unauthenticated('Credenciais inválidas.');
        }

        $this->limiter->clear($key);
        $this->audit->record('login.succeeded', $user->id, ip: $ip);

        return ['token' => $this->sessions->issue((int) $user->id), 'userId' => (int) $user->id];
    }
}
