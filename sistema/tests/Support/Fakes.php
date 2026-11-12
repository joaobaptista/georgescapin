<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Identity\GoogleIdentity;
use App\Application\Identity\GoogleIdentityProvider;
use App\Application\Identity\LoginCodeStore;
use App\Application\Identity\OAuthStateStore;
use App\Application\Shared\RateLimiter;
use App\Application\Shared\Transaction;
use App\Domain\Audit\AuditTrail;
use App\Domain\Catalog\Procedure;
use App\Domain\Catalog\ProcedureRepository;
use App\Domain\Clinical\Patient;
use App\Domain\Clinical\PatientRepository;
use App\Domain\Clinical\ProfessionalRepository;
use App\Domain\Identity\PasswordHasher;
use App\Domain\Identity\Session;
use App\Domain\Identity\SessionRepository;
use App\Domain\Identity\User;
use App\Domain\Identity\UserRepository;
use App\Domain\Scheduling\Appointment;
use App\Domain\Scheduling\AppointmentRepository;
use App\Domain\Scheduling\TimeSlot;
use App\Domain\Shared\Clock;

/** In-memory adapters: the domain and use cases are tested without a database. */
final class FixedClock implements Clock
{
    public function __construct(public \DateTimeImmutable $now = new \DateTimeImmutable('2030-01-10 09:00:00'))
    {
    }

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }
}

final class ImmediateTransaction implements Transaction
{
    public function run(callable $work): mixed
    {
        return $work();
    }
}

final class PlainHasher implements PasswordHasher
{
    public function hash(string $password): string
    {
        return "hashed:{$password}";
    }

    public function verify(string $password, string $hash): bool
    {
        return $hash === "hashed:{$password}";
    }
}

final class MemoryAudit implements AuditTrail
{
    /** @var list<string> */
    public array $actions = [];

    public function record(string $action, ?int $actorUserId, ?int $patientId = null, ?string $subjectType = null, ?int $subjectId = null, ?string $ip = null): void
    {
        $this->actions[] = $action;
    }
}

final class MemoryLimiter implements RateLimiter
{
    /** @var array<string, int> */
    public array $hits = [];

    public function hit(string $key, int $maxHits, int $decaySeconds): ?int
    {
        $this->hits[$key] = ($this->hits[$key] ?? 0) + 1;

        return $this->hits[$key] > $maxHits ? $decaySeconds : null;
    }

    public function blockedFor(string $key, int $maxHits): ?int
    {
        return ($this->hits[$key] ?? 0) >= $maxHits ? 60 : null;
    }

    public function clear(string $key): void
    {
        unset($this->hits[$key]);
    }
}

final class MemoryUsers implements UserRepository
{
    /** @var array<int, User> */
    public array $users = [];

    public function find(int $id): ?User
    {
        return $this->users[$id] ?? null;
    }

    public function findByEmail(string $email): ?User
    {
        foreach ($this->users as $user) {
            if ($user->email === $email) {
                return $user;
            }
        }

        return null;
    }

    public function findByGoogleSubject(string $subject): ?User
    {
        foreach ($this->users as $user) {
            if ($user->googleSubject === $subject) {
                return $user;
            }
        }

        return null;
    }

    public function emailInUse(string $email, ?int $exceptUserId = null): bool
    {
        $user = $this->findByEmail($email);

        return $user !== null && $user->id !== $exceptUserId;
    }

    public function save(User $user, \DateTimeImmutable $now): int
    {
        $id = $user->id ?? count($this->users) + 1;
        $this->users[$id] = $user->id === null
            ? new User($id, $user->name, $user->email, $user->passwordHash, $user->role, $user->activatedAt, $user->googleSubject)
            : $user;

        return $id;
    }
}

final class MemorySessions implements SessionRepository
{
    /** @var array<string, array{userId: int, created: \DateTimeImmutable, expires: \DateTimeImmutable}> */
    public array $sessions = [];

    public function create(int $userId, string $tokenHash, \DateTimeImmutable $createdAt, \DateTimeImmutable $expiresAt): void
    {
        $this->sessions[$tokenHash] = ['userId' => $userId, 'created' => $createdAt, 'expires' => $expiresAt];
    }

    public function findByTokenHash(string $tokenHash): ?Session
    {
        $s = $this->sessions[$tokenHash] ?? null;

        return $s !== null ? new Session(1, $s['userId'], $s['created'], $s['expires'], null) : null;
    }

    public function touch(int $sessionId, \DateTimeImmutable $at): void
    {
    }

    public function revoke(string $tokenHash): void
    {
        unset($this->sessions[$tokenHash]);
    }

    public function revokeAllForUser(int $userId, ?string $exceptTokenHash = null): void
    {
        foreach ($this->sessions as $hash => $session) {
            if ($session['userId'] === $userId && $hash !== $exceptTokenHash) {
                unset($this->sessions[$hash]);
            }
        }
    }
}

final class MemoryPatients implements PatientRepository
{
    /** @var array<int, Patient> */
    public array $patients = [];

    /** @var list<array{int, int}> [patientId, professionalId] pairs */
    public array $links = [];

    public function find(int $id): ?Patient
    {
        return $this->patients[$id] ?? null;
    }

    public function findByUser(int $userId): ?Patient
    {
        foreach ($this->patients as $patient) {
            if ($patient->userId === $userId) {
                return $patient;
            }
        }

        return null;
    }

    public function save(Patient $patient, \DateTimeImmutable $now): int
    {
        $id = $patient->id ?? count($this->patients) + 1;
        $this->patients[$id] = $patient;

        return $id;
    }

    public function isLinked(int $patientId, int $professionalId): bool
    {
        return in_array([$patientId, $professionalId], $this->links, true);
    }
}

final class MemoryProfessionals implements ProfessionalRepository
{
    public function exists(int $id): bool
    {
        return $id === 1;
    }

    public function findIdByUser(int $userId): ?int
    {
        return null;
    }

    public function add(int $userId, string $specialty, ?string $crm, ?string $city, ?string $state, \DateTimeImmutable $now): int
    {
        return 1;
    }
}

final class MemoryProcedures implements ProcedureRepository
{
    /** @var array<int, Procedure> */
    public array $procedures = [];

    public function find(int $id): ?Procedure
    {
        return $this->procedures[$id] ?? null;
    }

    public function save(Procedure $procedure, \DateTimeImmutable $now): int
    {
        $id = $procedure->id ?? count($this->procedures) + 1;
        $this->procedures[$id] = $procedure;

        return $id;
    }

    public function remove(int $id): void
    {
        unset($this->procedures[$id]);
    }
}

final class MemoryAppointments implements AppointmentRepository
{
    /** @var array<int, Appointment> */
    public array $appointments = [];

    public function __construct(private readonly ?ProcedureRepository $procedures = null)
    {
    }

    public function find(int $id): ?Appointment
    {
        return $this->appointments[$id] ?? null;
    }

    public function save(Appointment $appointment, \DateTimeImmutable $now): int
    {
        $id = $appointment->id ?? count($this->appointments) + 1;
        $this->appointments[$id] = Appointment::restore($id, $appointment->patientId, $appointment->professionalId, $appointment->procedureId(), $appointment->scheduledAt(), $appointment->status(), $appointment->notes());

        return $id;
    }

    public function remove(int $id): void
    {
        unset($this->appointments[$id]);
    }

    public function busySlots(int $professionalId, \DateTimeImmutable $from, \DateTimeImmutable $until, ?int $ignoreId): array
    {
        $slots = [];
        foreach ($this->appointments as $id => $a) {
            if ($a->professionalId === $professionalId && $id !== $ignoreId && $a->status()->occupiesSlot()
                && $a->scheduledAt() >= $from && $a->scheduledAt() < $until) {
                $minutes = $a->procedureId() !== null ? $this->procedures?->find($a->procedureId())?->durationMinutes : null;
                $slots[] = TimeSlot::starting($a->scheduledAt(), $minutes);
            }
        }

        return $slots;
    }
}

final class FakeGoogle implements GoogleIdentityProvider
{
    public ?GoogleIdentity $identity = null;

    /** @var list<array{code: string, verifier: string}> */
    public array $exchanges = [];

    public function isConfigured(): bool
    {
        return true;
    }

    public function authorizationUrl(string $state, string $codeChallenge, string $nonce): string
    {
        return 'https://google.test/auth?' . http_build_query(['state' => $state, 'code_challenge' => $codeChallenge, 'nonce' => $nonce]);
    }

    public function exchange(string $code, string $codeVerifier): GoogleIdentity
    {
        $this->exchanges[] = ['code' => $code, 'verifier' => $codeVerifier];

        return $this->identity;
    }
}

final class MemoryOAuthStates implements OAuthStateStore
{
    /** @var array<string, array{codeVerifier: string, nonce: string, createdAt: \DateTimeImmutable}> */
    public array $states = [];

    public function put(string $stateHash, string $codeVerifier, string $nonce, \DateTimeImmutable $createdAt): void
    {
        $this->states[$stateHash] = ['codeVerifier' => $codeVerifier, 'nonce' => $nonce, 'createdAt' => $createdAt];
    }

    public function take(string $stateHash): ?array
    {
        $entry = $this->states[$stateHash] ?? null;
        unset($this->states[$stateHash]);

        return $entry;
    }
}

final class MemoryLoginCodes implements LoginCodeStore
{
    /** @var array<string, array{userId: int, createdAt: \DateTimeImmutable}> */
    public array $codes = [];

    public function put(string $codeHash, int $userId, \DateTimeImmutable $createdAt): void
    {
        $this->codes[$codeHash] = ['userId' => $userId, 'createdAt' => $createdAt];
    }

    public function take(string $codeHash): ?array
    {
        $entry = $this->codes[$codeHash] ?? null;
        unset($this->codes[$codeHash]);

        return $entry;
    }
}
