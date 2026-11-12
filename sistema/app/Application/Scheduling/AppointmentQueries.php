<?php

declare(strict_types=1);

namespace App\Application\Scheduling;

/** Read side of the agenda, already in the API shape. */
interface AppointmentQueries
{
    public function find(int $id): ?array;

    /** @return list<array> */
    public function forProfessional(int $professionalId): array;

    /** @return list<array> */
    public function forPatient(int $patientId): array;
}
