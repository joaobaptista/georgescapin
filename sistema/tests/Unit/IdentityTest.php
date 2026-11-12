<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Application\Identity\ChangePassword;
use App\Application\Identity\Login;
use App\Application\Identity\SessionIssuer;
use App\Application\Identity\SessionPolicy;
use App\Application\Identity\UpdateProfile;
use App\Application\Shared\Actor;
use App\Domain\Identity\Role;
use App\Domain\Identity\Session;
use App\Domain\Identity\User;
use App\Domain\Shared\InvalidSecret;
use App\Domain\Shared\RuleViolation;
use App\Domain\Shared\TooManyAttempts;
use App\Domain\Shared\Unauthenticated;
use Tests\Support\FixedClock;
use Tests\Support\ImmediateTransaction;
use Tests\Support\MemoryAudit;
use Tests\Support\MemoryLimiter;
use Tests\Support\MemoryPatients;
use Tests\Support\MemorySessions;
use Tests\Support\MemoryUsers;
use Tests\Support\PlainHasher;
use Tests\Support\TestCase;

final class IdentityTest extends TestCase
{
    private MemoryUsers $users;
    private MemorySessions $sessions;
    private FixedClock $clock;

    public function __construct()
    {
        $this->users = new MemoryUsers();
        $this->users->save(new User(null, 'Ana', 'ana@x.test', 'hashed:senha-antiga', Role::Patient, new \DateTimeImmutable()), new \DateTimeImmutable());
        $this->sessions = new MemorySessions();
        $this->clock = new FixedClock();
    }

    private function actor(string $tokenHash = 'atual'): Actor
    {
        return new Actor(1, Role::Patient, null, null, $tokenHash, '10.0.0.1');
    }

    public function testSessionEndsWhenIdleOrAtItsAbsoluteLimit(): void
    {
        $start = new \DateTimeImmutable('2030-01-10 08:00');
        $session = new Session(1, 1, $start, $start->modify('+12 hours'), $start->modify('+1 hour'));
        self::assertTrue($session->isValidAt($start->modify('+2 hours'), 120));
        self::assertTrue(!$session->isValidAt($start->modify('+3 hours 1 minute'), 120), 'inatividade');
        $active = new Session(1, 1, $start, $start->modify('+12 hours'), $start->modify('+11 hours 59 minutes'));
        self::assertTrue(!$active->isValidAt($start->modify('+12 hours'), 120), 'limite absoluto');
    }

    public function testChangingThePasswordEndsOtherSessionsOnly(): void
    {
        $this->sessions->create(1, 'atual', $this->clock->now(), $this->clock->now()->modify('+1 hour'));
        $this->sessions->create(1, 'roubada', $this->clock->now(), $this->clock->now()->modify('+1 hour'));
        $change = new ChangePassword($this->users, new PlainHasher(), $this->sessions, new MemoryAudit(), $this->clock);

        self::assertThrows(InvalidSecret::class, fn () => $change($this->actor(), 'errada', 'nova-senha-1'));
        $change($this->actor(), 'senha-antiga', 'nova-senha-1');

        self::assertSame(['atual'], array_keys($this->sessions->sessions));
        self::assertSame('hashed:nova-senha-1', $this->users->find(1)->passwordHash);
    }

    public function testChangingTheEmailRequiresTheCurrentPassword(): void
    {
        $update = new UpdateProfile($this->users, new MemoryPatients(), new PlainHasher(), $this->sessions, new ImmediateTransaction(), new MemoryAudit(), $this->clock);

        self::assertThrows(RuleViolation::class, fn () => $update($this->actor(), ['name' => 'Ana', 'email' => 'novo@x.test']));
        self::assertThrows(InvalidSecret::class, fn () => $update($this->actor(), ['name' => 'Ana', 'email' => 'novo@x.test', 'current_password' => 'errada']));
        $update($this->actor(), ['name' => 'Ana Maria', 'email' => 'ana@x.test']); // same e-mail: no password needed
        $update($this->actor(), ['name' => 'Ana', 'email' => 'novo@x.test', 'current_password' => 'senha-antiga']);
        self::assertSame('novo@x.test', $this->users->find(1)->email);
    }

    public function testLoginCountsFailuresPerAccount(): void
    {
        $limiter = new MemoryLimiter();
        $audit = new MemoryAudit();
        $login = new Login($this->users, new PlainHasher(), new SessionIssuer($this->sessions, new SessionPolicy(), $this->clock), $limiter, $audit);

        for ($i = 0; $i < 10; $i++) {
            self::assertThrows(Unauthenticated::class, fn () => $login('ana@x.test', 'errada', null));
        }
        self::assertThrows(TooManyAttempts::class, fn () => $login('ana@x.test', 'senha-antiga', null));
        self::assertSame('login.failed', $audit->actions[0]);

        $limiter->hits = [];
        $session = $login('ana@x.test', 'senha-antiga', null);
        self::assertSame(1, $session['userId']);
        self::assertTrue(isset($this->sessions->sessions[hash('sha256', $session['token'])]), 'sessão não foi criada');
    }

    public function testUnknownEmailFailsLikeAWrongPassword(): void
    {
        $login = new Login($this->users, new PlainHasher(), new SessionIssuer($this->sessions, new SessionPolicy(), $this->clock), new MemoryLimiter(), new MemoryAudit());
        self::assertThrows(Unauthenticated::class, fn () => $login('ninguem@x.test', 'qualquer', null), 'Credenciais inválidas');
    }
}
