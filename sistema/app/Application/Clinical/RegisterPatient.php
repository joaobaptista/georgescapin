<?php

declare(strict_types=1);

namespace App\Application\Clinical;

use App\Application\Shared\Actor;
use App\Application\Shared\Transaction;
use App\Domain\Clinical\MedicalRecord;
use App\Domain\Clinical\MedicalRecordRepository;
use App\Domain\Clinical\Patient;
use App\Domain\Clinical\PatientRepository;
use App\Domain\Identity\PasswordHasher;
use App\Domain\Identity\Role;
use App\Domain\Identity\User;
use App\Domain\Identity\UserRepository;
use App\Domain\Shared\Clock;
use App\Domain\Shared\RuleViolation;

/** A professional registers a patient at the clinic. The account starts pending. */
final class RegisterPatient
{
    /** Reserved domain (RFC 2606): never belongs to anyone. */
    public const PLACEHOLDER_EMAIL_DOMAIN = '@sem-email.invalid';

    public function __construct(
        private readonly UserRepository $users,
        private readonly PatientRepository $patients,
        private readonly MedicalRecordRepository $records,
        private readonly PasswordHasher $hasher,
        private readonly Transaction $transaction,
        private readonly Clock $clock,
    ) {
    }

    /** @param array{name: string, cpf: string, phone: string, birth_date?: ?string, email?: ?string, allergies?: ?string, notes?: ?string} $input */
    public function __invoke(Actor $actor, array $input): int
    {
        $professionalId = $actor->professionalIdOrFail('Apenas profissionais de saúde podem cadastrar novos pacientes.');

        $email = $input['email'] ?? null;
        if ($email && $this->users->emailInUse($email)) {
            throw new RuleViolation('Este e-mail já está em uso.', [
                'email' => ['Este e-mail já pertence a outra conta. Se o paciente já tem cadastro, peça que ele agende pelo portal.'],
            ]);
        }
        $email = $email ?: 'paciente_' . bin2hex(random_bytes(6)) . self::PLACEHOLDER_EMAIL_DOMAIN;

        return $this->transaction->run(function () use ($input, $email, $professionalId): int {
            $now = $this->clock->now();
            // Unknown random password: the patient sets their own through the activation link.
            $user = new User(null, $input['name'], $email, $this->hasher->hash(bin2hex(random_bytes(16))), Role::Patient, null);
            $userId = $this->users->save($user, $now);
            $patientId = $this->patients->save(
                new Patient(null, $userId, $professionalId, $input['cpf'], $input['phone'], $input['birth_date'] ?? null),
                $now,
            );

            $initialNotes = self::initialNotes($input['allergies'] ?? null, $input['notes'] ?? null);
            if ($initialNotes !== null) {
                $this->records->append(new MedicalRecord($patientId, $professionalId, null, $initialNotes, null, $now));
            }

            return $patientId;
        });
    }

    public static function isPlaceholderEmail(?string $email): bool
    {
        return $email === null
            || str_ends_with($email, self::PLACEHOLDER_EMAIL_DOMAIN)
            || preg_match('/^paciente_[0-9a-f]+@nuva\.com\.br$/', $email) === 1;
    }

    private static function initialNotes(?string $allergies, ?string $notes): ?string
    {
        if (empty($allergies) && empty($notes)) {
            return null;
        }
        $text = "Cadastro Inicial.\n";
        if (!empty($allergies)) {
            $text .= "Alergias/Restrições: {$allergies}\n";
        }
        if (!empty($notes)) {
            $text .= "Informações: {$notes}\n";
        }

        return $text;
    }
}
