<?php

declare(strict_types=1);

namespace App\Application\Clinical;

use App\Application\Identity\PasswordResetLinks;
use App\Application\Shared\Actor;
use App\Domain\Audit\AuditTrail;
use App\Domain\Clinical\ClinicalAccess;
use App\Domain\Clinical\PatientRepository;
use App\Domain\Identity\UserRepository;
use App\Domain\Shared\AccessDenied;
use App\Domain\Shared\Clock;
use App\Domain\Shared\Conflict;
use App\Domain\Shared\NotFound;
use App\Domain\Shared\RuleViolation;

/**
 * Link for a clinic-registered patient to create their own password. Only for
 * accounts never activated and only by the registering professional: issuing
 * it for an active account would hand the professional the patient's account,
 * including records written by other professionals.
 */
final class GenerateActivationLink
{
    public function __construct(
        private readonly PatientRepository $patients,
        private readonly UserRepository $users,
        private readonly ClinicalAccess $access,
        private readonly PasswordResetLinks $links,
        private readonly AuditTrail $audit,
        private readonly Clock $clock,
    ) {
    }

    /** @return array{email: string, activation_path: string, expires_in_hours: int} */
    public function __invoke(Actor $actor, int $patientId, ?string $newEmail): array
    {
        $professionalId = $actor->professionalId;
        $patient = $this->patients->find($patientId);
        if ($patient === null || $professionalId === null || !$this->access->professionalCanAccess($professionalId, $patientId)) {
            throw new NotFound('Paciente não encontrado entre os seus pacientes.');
        }
        if (!$patient->wasRegisteredBy($professionalId)) {
            throw new AccessDenied('Somente o profissional que cadastrou o paciente pode gerar o link de ativação.');
        }

        $user = $this->users->find((int) $patient->userId) ?? throw new NotFound('Paciente sem conta de acesso.');
        if ($user->isActive()) {
            throw new Conflict('Esta conta já está ativa. Se o paciente esqueceu a senha, a administração da clínica gera um link de redefinição.');
        }

        if ($newEmail !== null && strcasecmp($newEmail, $user->email) !== 0) {
            if ($this->users->emailInUse($newEmail, $user->id)) {
                throw new RuleViolation('Este e-mail já está em uso.', [
                    'email' => ['Este e-mail já pertence a outra conta. Se o paciente já tem cadastro, peça que ele agende pelo portal.'],
                ]);
            }
            $this->links->consume($user->email);
            $user->email = $newEmail;
            $this->users->save($user, $this->clock->now());
        }

        if (RegisterPatient::isPlaceholderEmail($user->email)) {
            throw new RuleViolation('Informe o e-mail do paciente: ele será o login de acesso ao portal.', [
                'email' => ['Informe o e-mail do paciente para gerar o link de ativação.'],
            ]);
        }

        $path = $this->links->issue($user->email);
        $this->audit->record('patient.activation_link_issued', $actor->userId, $patientId, 'patient', $patientId, $actor->ip);

        return ['email' => $user->email, 'activation_path' => $path, 'expires_in_hours' => PasswordResetLinks::TTL_HOURS];
    }
}
