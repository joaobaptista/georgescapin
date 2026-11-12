<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Shared\Transaction;
use App\Domain\Audit\AuditTrail;
use App\Domain\Clinical\Patient;
use App\Domain\Clinical\PatientRepository;
use App\Domain\Identity\PasswordHasher;
use App\Domain\Identity\Role;
use App\Domain\Identity\User;
use App\Domain\Identity\UserRepository;
use App\Domain\Shared\AccessDenied;
use App\Domain\Shared\Clock;
use App\Domain\Shared\Unauthenticated;

/**
 * Handles Google's callback: signs in, links or creates the account, and
 * returns a one-time login code for the browser.
 *
 * Account rules:
 *  - the e-mail must be verified by Google;
 *  - an existing active account with that e-mail is linked to the Google id;
 *  - accounts registered by the clinic and not yet activated are refused: if
 *    the clinic typed someone else's e-mail, that person would otherwise reach
 *    the patient's records. They keep using the activation link;
 *  - new people get a patient account (public sign-up never creates
 *    professionals).
 */
final class CompleteGoogleSignIn
{
    private const STATE_TTL_SECONDS = 600;

    public function __construct(
        private readonly GoogleIdentityProvider $google,
        private readonly OAuthStateStore $states,
        private readonly LoginCodeStore $loginCodes,
        private readonly UserRepository $users,
        private readonly PatientRepository $patients,
        private readonly PasswordHasher $hasher,
        private readonly Transaction $transaction,
        private readonly AuditTrail $audit,
        private readonly Clock $clock,
    ) {
    }

    /** @return string one-time login code (valid for 60 seconds) */
    public function __invoke(string $code, string $state, ?string $browserState, ?string $ip): string
    {
        $expired = new Unauthenticated('O login com o Google expirou ou foi iniciado em outro navegador. Tente novamente.');
        // The state must match the cookie set when this browser started the sign-in (login CSRF).
        if ($browserState === null || $browserState === '' || !hash_equals($browserState, $state)) {
            throw $expired;
        }
        $pending = $this->states->take(hash('sha256', $state)) ?? throw $expired;
        if ($this->clock->now()->getTimestamp() - $pending['createdAt']->getTimestamp() > self::STATE_TTL_SECONDS) {
            throw $expired;
        }

        $identity = $this->google->exchange($code, $pending['codeVerifier']);
        if ($identity->nonce === null || !hash_equals($pending['nonce'], $identity->nonce)) {
            throw new Unauthenticated('Resposta do Google inválida. Tente novamente.');
        }
        if (!$identity->emailVerified) {
            throw new AccessDenied('O Google não confirmou este e-mail. Use uma conta Google com e-mail verificado.');
        }

        $user = $this->resolveAccount($identity, $ip);
        $this->audit->record('login.succeeded_google', $user->id, ip: $ip);

        $loginCode = bin2hex(random_bytes(32));
        $this->loginCodes->put(hash('sha256', $loginCode), (int) $user->id, $this->clock->now());

        return $loginCode;
    }

    private function resolveAccount(GoogleIdentity $identity, ?string $ip): User
    {
        $email = mb_strtolower(trim($identity->email));
        $user = $this->users->findByGoogleSubject($identity->subject) ?? $this->users->findByEmail($email);

        if ($user === null) {
            return $this->registerPatient($identity, $email, $ip);
        }
        if ($user->googleSubject !== null && $user->googleSubject !== $identity->subject) {
            throw new AccessDenied('Este e-mail já está vinculado a outra conta Google.');
        }
        if (!$user->isActive()) {
            throw new AccessDenied('Sua conta foi criada pela clínica e ainda não foi ativada. Use o link de ativação enviado pela clínica.');
        }
        if ($user->googleSubject === null) {
            $user->linkGoogle($identity->subject);
            $this->users->save($user, $this->clock->now());
            $this->audit->record('account.google_linked', $user->id, ip: $ip);
        }

        return $user;
    }

    private function registerPatient(GoogleIdentity $identity, string $email, ?string $ip): User
    {
        $user = $this->transaction->run(function () use ($identity, $email): User {
            $now = $this->clock->now();
            // No password: the account signs in with Google (a password can be
            // set later through a reset link issued by the clinic).
            $user = new User(null, mb_substr($identity->name, 0, 255), $email, $this->hasher->hash(bin2hex(random_bytes(32))), Role::Patient, $now, $identity->subject);
            $userId = $this->users->save($user, $now);
            $this->patients->save(new Patient(null, $userId, null, null, null, null), $now);

            return $this->users->find($userId);
        });
        $this->audit->record('account.registered_google', $user->id, ip: $ip);

        return $user;
    }
}
