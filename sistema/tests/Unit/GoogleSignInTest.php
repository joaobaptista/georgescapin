<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Application\Identity\CompleteGoogleSignIn;
use App\Application\Identity\ExchangeLoginCode;
use App\Application\Identity\GoogleIdentity;
use App\Application\Identity\SessionIssuer;
use App\Application\Identity\SessionPolicy;
use App\Application\Identity\StartGoogleSignIn;
use App\Domain\Identity\Role;
use App\Domain\Identity\User;
use App\Domain\Shared\AccessDenied;
use App\Domain\Shared\Unauthenticated;
use Tests\Support\FakeGoogle;
use Tests\Support\FixedClock;
use Tests\Support\ImmediateTransaction;
use Tests\Support\MemoryAudit;
use Tests\Support\MemoryLoginCodes;
use Tests\Support\MemoryOAuthStates;
use Tests\Support\MemoryPatients;
use Tests\Support\MemorySessions;
use Tests\Support\MemoryUsers;
use Tests\Support\PlainHasher;
use Tests\Support\TestCase;

final class GoogleSignInTest extends TestCase
{
    private FakeGoogle $google;
    private MemoryOAuthStates $states;
    private MemoryLoginCodes $codes;
    private MemoryUsers $users;
    private MemoryPatients $patients;
    private MemoryAudit $audit;
    private FixedClock $clock;

    public function __construct()
    {
        $this->google = new FakeGoogle();
        $this->states = new MemoryOAuthStates();
        $this->codes = new MemoryLoginCodes();
        $this->users = new MemoryUsers();
        $this->patients = new MemoryPatients();
        $this->audit = new MemoryAudit();
        $this->clock = new FixedClock();
    }

    /** Starts a sign-in and makes Google answer with $identity (echoing the request nonce). */
    private function signIn(string $email, bool $verified = true, string $sub = 'google-1', ?string $browserState = 'same'): string
    {
        $start = (new StartGoogleSignIn($this->google, $this->states, $this->clock))();
        parse_str((string) parse_url($start['url'], PHP_URL_QUERY), $query);
        $this->google->identity = new GoogleIdentity($sub, $email, $verified, 'Ana Google', $query['nonce']);

        return ($this->complete())('code-1', $start['state'], $browserState === 'same' ? $start['state'] : $browserState, '10.0.0.1');
    }

    private function complete(): CompleteGoogleSignIn
    {
        return new CompleteGoogleSignIn($this->google, $this->states, $this->codes, $this->users, $this->patients, new PlainHasher(), new ImmediateTransaction(), $this->audit, $this->clock);
    }

    public function testNewPersonGetsAnActivePatientAccountLinkedToGoogle(): void
    {
        $this->signIn('nova@gmail.com');
        $user = $this->users->findByEmail('nova@gmail.com');
        self::assertSame(Role::Patient, $user->role);
        self::assertSame('google-1', $user->googleSubject);
        self::assertTrue($user->isActive());
        self::assertTrue($this->patients->findByUser((int) $user->id) !== null, 'perfil de paciente não criado');
        self::assertSame(['account.registered_google', 'login.succeeded_google'], $this->audit->actions);
    }

    public function testPkceVerifierMatchesTheChallengeSentToGoogle(): void
    {
        $start = (new StartGoogleSignIn($this->google, $this->states, $this->clock))();
        parse_str((string) parse_url($start['url'], PHP_URL_QUERY), $query);
        $this->google->identity = new GoogleIdentity('g', 'x@gmail.com', true, 'X', $query['nonce']);
        ($this->complete())('c', $start['state'], $start['state'], null);
        $verifier = $this->google->exchanges[0]['verifier'];
        self::assertSame($query['code_challenge'], rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='));
    }

    public function testExistingActiveAccountIsLinkedByVerifiedEmail(): void
    {
        $this->users->save(new User(null, 'Ana', 'ana@gmail.com', 'hashed:x', Role::Patient, new \DateTimeImmutable()), new \DateTimeImmutable());
        $this->signIn('ana@gmail.com');
        self::assertSame('google-1', $this->users->find(1)->googleSubject);
        self::assertSame(1, count($this->users->users), 'criou conta duplicada');
        self::assertTrue(in_array('account.google_linked', $this->audit->actions, true));
    }

    public function testPendingClinicAccountsMustUseTheActivationLink(): void
    {
        $this->users->save(new User(null, 'Paciente', 'paciente@gmail.com', 'hashed:x', Role::Patient, null), new \DateTimeImmutable());
        self::assertThrows(AccessDenied::class, fn () => $this->signIn('paciente@gmail.com'), 'link de ativação');
        self::assertSame(null, $this->users->find(1)->googleSubject);
    }

    public function testUnverifiedEmailIsRefused(): void
    {
        $this->users->save(new User(null, 'Ana', 'ana@empresa.com', 'hashed:x', Role::Professional, new \DateTimeImmutable()), new \DateTimeImmutable());
        self::assertThrows(AccessDenied::class, fn () => $this->signIn('ana@empresa.com', false), 'não confirmou');
        self::assertSame(null, $this->users->find(1)->googleSubject);
    }

    public function testStateFromAnotherBrowserIsRefused(): void
    {
        self::assertThrows(Unauthenticated::class, fn () => $this->signIn('x@gmail.com', true, 'g', 'state-de-outro-navegador'));
        self::assertThrows(Unauthenticated::class, fn () => $this->signIn('x@gmail.com', true, 'g', null));
        self::assertSame([], $this->google->exchanges, 'trocou o código sem validar o state');
    }

    public function testNonceMismatchAndReusedStateAreRefused(): void
    {
        $start = (new StartGoogleSignIn($this->google, $this->states, $this->clock))();
        $this->google->identity = new GoogleIdentity('g', 'x@gmail.com', true, 'X', 'outro-nonce');
        self::assertThrows(Unauthenticated::class, fn () => ($this->complete())('c', $start['state'], $start['state'], null));
        // The state was consumed by the first attempt.
        self::assertThrows(Unauthenticated::class, fn () => ($this->complete())('c', $start['state'], $start['state'], null), 'expirou');
    }

    public function testEmailLinkedToAnotherGoogleAccountIsRefused(): void
    {
        $this->users->save(new User(null, 'Ana', 'ana@gmail.com', 'hashed:x', Role::Patient, new \DateTimeImmutable(), 'google-original'), new \DateTimeImmutable());
        self::assertThrows(AccessDenied::class, fn () => $this->signIn('ana@gmail.com', true, 'google-outro'), 'outra conta Google');
    }

    public function testLoginCodeIsSingleUseAndShortLived(): void
    {
        $sessions = new MemorySessions();
        $exchange = new ExchangeLoginCode($this->codes, new SessionIssuer($sessions, new SessionPolicy(), $this->clock), $this->clock);

        $code = $this->signIn('nova@gmail.com');
        $session = $exchange($code);
        self::assertSame(1, $session['userId']);
        self::assertThrows(Unauthenticated::class, fn () => $exchange($code));

        $late = $this->signIn('nova@gmail.com');
        $this->clock->now = $this->clock->now->modify('+61 seconds');
        self::assertThrows(Unauthenticated::class, fn () => $exchange($late));
    }
}
