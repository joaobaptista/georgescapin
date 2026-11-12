<?php

declare(strict_types=1);

/**
 * Authenticated, mutating API end-to-end test.
 *
 * It is deliberately limited to a local, throwaway SQLite database. It creates
 * accounts, appointments, procedures, patients and records, then removes its
 * own data. Never point it at a production database.
 *
 * Usage:
 *   NUVA_E2E_BASE_URL=http://127.0.0.1:8000 \
 *   NUVA_E2E_DB_PATH=/private/tmp/nuva-e2e/nuva.sqlite \
 *   NUVA_E2E_ALLOW_MUTATIONS=1 \
 *   php scripts/api-e2e-test.php
 */

const REQUEST_TIMEOUT_SECONDS = 10;

$baseUrl = rtrim((string) getenv('NUVA_E2E_BASE_URL'), '/');
$databasePath = (string) getenv('NUVA_E2E_DB_PATH');
$allowsMutations = getenv('NUVA_E2E_ALLOW_MUTATIONS') === '1';
$projectRoot = dirname(__DIR__);

if ($baseUrl === '' || !$allowsMutations || $databasePath === '') {
    fwrite(STDERR, "Set NUVA_E2E_BASE_URL, NUVA_E2E_DB_PATH and NUVA_E2E_ALLOW_MUTATIONS=1.\n");
    exit(2);
}

$baseParts = parse_url($baseUrl);
$host = is_array($baseParts) ? ($baseParts['host'] ?? '') : '';
if (!in_array($host, ['127.0.0.1', 'localhost', '::1'], true)) {
    fwrite(STDERR, "For safety, api-e2e-test.php only runs against localhost.\n");
    exit(2);
}

$realDatabasePath = realpath($databasePath);
$defaultDatabasePath = realpath($projectRoot . '/database/database.sqlite');
if ($realDatabasePath === false || $realDatabasePath === $defaultDatabasePath) {
    fwrite(STDERR, "NUVA_E2E_DB_PATH must be an existing temporary SQLite database, never database/database.sqlite.\n");
    exit(2);
}

/**
 * @return array{status: int, body: string, headers: list<string>, json: mixed, error: ?string}
 */
function requestApi(string $baseUrl, string $method, string $path, ?array $payload = null, ?string $token = null): array
{
    $headers = [
        'Accept: application/json',
        'Connection: close',
        'User-Agent: nuva-api-e2e-test/1.0',
    ];
    $content = null;

    if ($token !== null) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    if ($payload !== null) {
        $headers[] = 'Content-Type: application/json';
        $content = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    $context = stream_context_create(['http' => [
        'method' => $method,
        'header' => implode("\r\n", $headers),
        'content' => $content,
        'ignore_errors' => true,
        'timeout' => REQUEST_TIMEOUT_SECONDS,
        'follow_location' => 0,
        'protocol_version' => 1.1,
    ]]);

    $warning = null;
    set_error_handler(static function (int $severity, string $message) use (&$warning): bool {
        $warning = $message;
        return true;
    });
    $body = file_get_contents($baseUrl . $path, false, $context);
    restore_error_handler();

    /** @var list<string> $http_response_header */
    $responseHeaders = $http_response_header ?? [];
    $status = 0;
    $isJson = false;
    foreach ($responseHeaders as $header) {
        if (preg_match('/^HTTP\/\S+\s+(\d{3})\b/', $header, $matches) === 1) {
            $status = (int) $matches[1];
        }
        if (stripos($header, 'Content-Type:') === 0 && stripos($header, 'application/json') !== false) {
            $isJson = true;
        }
    }

    $responseBody = $body === false ? '' : $body;
    $decoded = null;
    if ($isJson && $responseBody !== '') {
        $decoded = json_decode($responseBody, true);
    }

    return [
        'status' => $status,
        'body' => $responseBody,
        'headers' => $responseHeaders,
        'json' => $decoded,
        'error' => $body === false ? ($warning ?? 'HTTP request failed.') : null,
    ];
}

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$passed = 0;
$failed = 0;

/**
 * @param int|list<int> $expectedStatuses
 * @param null|callable(mixed): void $validate
 */
function expectResponse(string $name, array $response, int|array $expectedStatuses, ?callable $validate = null): mixed
{
    global $passed, $failed;

    $expected = is_array($expectedStatuses) ? $expectedStatuses : [$expectedStatuses];
    $requiresJson = !in_array($response['status'], [204], true);
    $failure = null;

    if (!in_array($response['status'], $expected, true)) {
        $preview = trim((string) preg_replace('/\s+/', ' ', $response['body']));
        $failure = 'HTTP ' . $response['status'] . '; expected ' . implode(' or ', $expected)
            . ($preview === '' ? '' : '; response ' . mb_strimwidth($preview, 0, 240, '…'));
    } elseif ($response['error'] !== null) {
        $failure = $response['error'];
    } elseif ($requiresJson && !is_array($response['json'])) {
        $failure = 'expected a JSON response';
    } elseif ($validate !== null) {
        try {
            $validate($response['json']);
        } catch (Throwable $error) {
            $failure = $error->getMessage();
        }
    }

    $ok = $failure === null;
    printf("%s %s — %s\n", $ok ? 'PASS' : 'FAIL', $name, $ok ? 'HTTP ' . $response['status'] : $failure);
    if ($ok) {
        $passed++;
    } else {
        $failed++;
        throw new RuntimeException($name . ': ' . $failure);
    }

    return $response['json'];
}

function listContainsId(mixed $payload, int $id): bool
{
    $items = is_array($payload) && array_is_list($payload)
        ? $payload
        : (is_array($payload) ? ($payload['data'] ?? []) : []);

    foreach ($items as $item) {
        if (is_array($item) && (int) ($item['id'] ?? 0) === $id) {
            return true;
        }
    }

    return false;
}

/** @param list<string> $emails */
function cleanupTestData(PDO $database, array $emails, ?int $procedureId): void
{
    try {
        $database->beginTransaction();
        foreach ($emails as $email) {
            $userStatement = $database->prepare('SELECT id FROM users WHERE email = ?');
            $userStatement->execute([$email]);
            $userId = $userStatement->fetchColumn();
            if ($userId === false) {
                continue;
            }

            $patientStatement = $database->prepare('SELECT id FROM patients WHERE user_id = ?');
            $patientStatement->execute([(int) $userId]);
            $patientId = $patientStatement->fetchColumn();
            if ($patientId !== false) {
                $database->prepare('DELETE FROM medical_records WHERE patient_id = ?')->execute([(int) $patientId]);
                $database->prepare('DELETE FROM appointments WHERE patient_id = ?')->execute([(int) $patientId]);
                $database->prepare('DELETE FROM patients WHERE id = ?')->execute([(int) $patientId]);
            }

            $database->prepare('DELETE FROM personal_access_tokens WHERE user_id = ?')->execute([(int) $userId]);
            $database->prepare('DELETE FROM password_reset_tokens WHERE email = ?')->execute([$email]);
            $database->prepare('DELETE FROM professionals WHERE user_id = ?')->execute([(int) $userId]);
            $database->prepare('DELETE FROM users WHERE id = ?')->execute([(int) $userId]);
        }

        if ($procedureId !== null) {
            $database->prepare('DELETE FROM appointments WHERE procedure_id = ?')->execute([$procedureId]);
            $database->prepare('DELETE FROM procedures WHERE id = ?')->execute([$procedureId]);
        }

        $database->commit();
    } catch (Throwable $error) {
        if ($database->inTransaction()) {
            $database->rollBack();
        }
        fwrite(STDERR, "Cleanup warning: {$error->getMessage()}\n");
    }
}

/** Clear only the local test's API login limiter, so the test is deterministic. */
/** Clear the local test's rate-limit counters (stored in the database), so the test is deterministic. */
function resetLocalLoginRateLimit(PDO $database): void
{
    $database->exec('DELETE FROM rate_limits');
}

/** Timestamps written directly by the test use the server's clinic timezone. */
function clinicNow(string $modifier = 'now'): string
{
    $timezone = new DateTimeZone(getenv('APP_TIMEZONE') ?: 'America/Sao_Paulo');

    return (new DateTimeImmutable($modifier, $timezone))->format('Y-m-d H:i:s');
}

$database = new PDO('sqlite:' . $realDatabasePath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$database->exec('PRAGMA foreign_keys = ON');
$suffix = gmdate('YmdHis') . '-' . bin2hex(random_bytes(3));
$testEmails = [];
$procedureId = null;

echo "Nüva authenticated API end-to-end test\n";
echo "Base URL: {$baseUrl}\n";
echo "Database: temporary SQLite file\n\n";

try {
    resetLocalLoginRateLimit($database);

    $professionalLogin = expectResponse(
        'Professional login',
        requestApi($baseUrl, 'POST', '/api/login', ['email' => 'dr.gabriel@nuva.com.br', 'password' => 'senha123']),
        200,
        static function (mixed $payload): void {
            assertTrue(($payload['user']['role'] ?? null) === 'professional', 'professional role was not returned');
            assertTrue(is_string($payload['token'] ?? null) && $payload['token'] !== '', 'professional token was not returned');
        }
    );
    $professionalToken = $professionalLogin['token'];
    $professionalId = (int) ($professionalLogin['user']['professional']['id'] ?? 0);
    assertTrue($professionalId > 0, 'seed professional profile is missing');

    expectResponse('Public professional search', requestApi($baseUrl, 'GET', '/api/professionals/search?q=Gabriel'), 200, static function (mixed $payload): void {
        assertTrue(is_array($payload) && $payload !== [], 'public search returned no professional');
    });
    expectResponse('Professional dashboard', requestApi($baseUrl, 'GET', '/api/professional/dashboard', null, $professionalToken), 200);
    expectResponse('Professional appointments list', requestApi($baseUrl, 'GET', '/api/appointments', null, $professionalToken), 200);

    $registeredEmail = "e2e-patient-{$suffix}@example.test";
    $registeredPassword = "Nuva-E2E-{$suffix}!";
    $testEmails[] = $registeredEmail;
    $registered = expectResponse(
        'Patient registration',
        requestApi($baseUrl, 'POST', '/api/register', [
            'name' => 'Paciente E2E ' . $suffix,
            'email' => $registeredEmail,
            'password' => $registeredPassword,
            'password_confirmation' => $registeredPassword,
            'role' => 'patient',
            'phone' => '11999990000',
        ]),
        201,
        static function (mixed $payload): void {
            assertTrue(($payload['user']['role'] ?? null) === 'patient', 'patient role was not returned');
            assertTrue(is_string($payload['token'] ?? null) && $payload['token'] !== '', 'patient token was not returned');
        }
    );
    $patientId = (int) ($registered['user']['patient']['id'] ?? 0);
    assertTrue($patientId > 0, 'registered patient profile is missing');
    $patientToken = $registered['token'];

    expectResponse('Patient current user', requestApi($baseUrl, 'GET', '/api/user', null, $patientToken), 200, static function (mixed $payload) use ($registeredEmail): void {
        assertTrue(($payload['email'] ?? null) === $registeredEmail, 'current user does not match the session');
    });
    expectResponse('Patient profile update', requestApi($baseUrl, 'PUT', '/api/user/profile', [
        'name' => 'Paciente E2E Atualizado ' . $suffix,
        'email' => $registeredEmail,
        'phone' => '11988880000',
        'cpf' => '000.000.000-00',
        'birth_date' => '1990-01-01',
    ], $patientToken), 200);
    expectResponse('E-mail change requires the current password', requestApi($baseUrl, 'PUT', '/api/user/profile', [
        'name' => 'Paciente E2E', 'email' => "outro-{$suffix}@example.test",
    ], $patientToken), 422);
    expectResponse('E-mail change rejects a wrong current password', requestApi($baseUrl, 'PUT', '/api/user/profile', [
        'name' => 'Paciente E2E', 'email' => "outro-{$suffix}@example.test", 'current_password' => 'senha-errada',
    ], $patientToken), 400);

    $changedPassword = "Nuva-Changed-{$suffix}!";
    expectResponse('Patient password update', requestApi($baseUrl, 'PUT', '/api/user/password', [
        'current_password' => $registeredPassword,
        'password' => $changedPassword,
        'password_confirmation' => $changedPassword,
    ], $patientToken), 200);

    $patientRelogin = expectResponse('Patient login with changed password', requestApi($baseUrl, 'POST', '/api/login', [
        'email' => $registeredEmail,
        'password' => $changedPassword,
    ]), 200);
    $patientToken = $patientRelogin['token'];

    $activationToken = bin2hex(random_bytes(24));
    $database->prepare('DELETE FROM password_reset_tokens WHERE email = ?')->execute([$registeredEmail]);
    $database->prepare('INSERT INTO password_reset_tokens (email, token, created_at) VALUES (?, ?, ?)')
        ->execute([$registeredEmail, hash('sha256', $activationToken), clinicNow()]);
    $activatedPassword = "Nuva-Activated-{$suffix}!";
    expectResponse('Account activation', requestApi($baseUrl, 'POST', '/api/activate-account', [
        'email' => $registeredEmail,
        'token' => $activationToken,
        'password' => $activatedPassword,
        'password_confirmation' => $activatedPassword,
    ]), 200, static function (mixed $payload): void {
        assertTrue(($payload['success'] ?? false) === true, 'activation did not return success');
    });
    $patientRelogin = expectResponse('Patient login with activated password', requestApi($baseUrl, 'POST', '/api/login', [
        'email' => $registeredEmail,
        'password' => $activatedPassword,
    ]), 200);
    $patientToken = $patientRelogin['token'];

    expectResponse('Patient appointment list', requestApi($baseUrl, 'GET', '/api/patient/appointments', null, $patientToken), 200);
    expectResponse('Patient medical-record list', requestApi($baseUrl, 'GET', '/api/patient/medical-records', null, $patientToken), 200);
    expectResponse('Patient cannot list procedures', requestApi($baseUrl, 'GET', '/api/procedures', null, $patientToken), 403);
    expectResponse('Patient cannot open professional dashboard', requestApi($baseUrl, 'GET', '/api/professional/dashboard', null, $patientToken), 403);
    expectResponse('Professional cannot use patient-only route', requestApi($baseUrl, 'GET', '/api/patient/appointments', null, $professionalToken), 403);

    $procedures = expectResponse('Professional procedure list', requestApi($baseUrl, 'GET', '/api/procedures', null, $professionalToken), 200, static function (mixed $payload): void {
        assertTrue(is_array($payload) && $payload !== [], 'seed procedures were not returned');
    });
    assertTrue(is_array($procedures), 'procedure list is not an array');

    $newProcedure = expectResponse('Procedure create', requestApi($baseUrl, 'POST', '/api/procedures', [
        'name' => 'Procedimento E2E ' . $suffix,
        'category' => 'Teste automatizado',
        'description' => 'Registro temporário do teste E2E.',
        'base_price' => 123.45,
        'duration_minutes' => 35,
    ], $professionalToken), 201);
    $procedureId = (int) ($newProcedure['id'] ?? 0);
    assertTrue($procedureId > 0, 'procedure ID was not returned');
    expectResponse('Procedure show', requestApi($baseUrl, 'GET', "/api/procedures/{$procedureId}", null, $professionalToken), 200);
    expectResponse('Procedure update', requestApi($baseUrl, 'PUT', "/api/procedures/{$procedureId}", [
        'name' => 'Procedimento E2E atualizado ' . $suffix,
        'category' => 'Teste atualizado',
        'description' => 'Registro temporário atualizado.',
        'base_price' => 150.00,
        'duration_minutes' => 45,
    ], $professionalToken), 200);
    expectResponse('Procedure validation', requestApi($baseUrl, 'POST', '/api/procedures', [], $professionalToken), 422);

    // A self-registered patient is not linked to any professional yet: the
    // professional can neither book, list, search nor open them.
    expectResponse('Professional cannot book an unlinked patient', requestApi($baseUrl, 'POST', '/api/appointments', [
        'patient_id' => $patientId,
        'scheduled_at' => '2031-08-19 09:00:00',
    ], $professionalToken), 403);
    expectResponse('Unlinked patient is not listed', requestApi($baseUrl, 'GET', '/api/patients', null, $professionalToken), 200, static function (mixed $payload) use ($patientId): void {
        assertTrue(!listContainsId($payload, $patientId), 'unlinked self-registered patient was listed');
    });
    expectResponse('Unlinked patient is not searchable', requestApi($baseUrl, 'GET', '/api/patients/search?q=E2E', null, $professionalToken), 200, static function (mixed $payload) use ($patientId): void {
        assertTrue(!listContainsId($payload, $patientId), 'unlinked self-registered patient was returned in search');
    });
    expectResponse('Unlinked patient records are protected', requestApi($baseUrl, 'GET', "/api/patients/{$patientId}/records", null, $professionalToken), 403);
    expectResponse('Patient cannot book a missing professional', requestApi($baseUrl, 'POST', '/api/appointments', [
        'professional_id' => 999999999,
        'scheduled_at' => '2031-08-21 10:00:00',
    ], $patientToken), 422);

    expectResponse('Patient cannot book in the past', requestApi($baseUrl, 'POST', '/api/appointments', [
        'professional_id' => $professionalId,
        'scheduled_at' => '2020-01-10 10:00',
    ], $patientToken), 422);
    expectResponse('Patient cannot book with a malformed date', requestApi($baseUrl, 'POST', '/api/appointments', [
        'professional_id' => $professionalId,
        'scheduled_at' => 'amanhã cedo',
    ], $patientToken), 422);

    // The patient's own booking request is what links them to the professional.
    $patientAppointment = expectResponse('Patient appointment create', requestApi($baseUrl, 'POST', '/api/appointments', [
        'professional_id' => $professionalId,
        'scheduled_at' => '2031-08-21 10:00:00',
        'notes' => 'Solicitação temporária feita pelo paciente.',
    ], $patientToken), 201);
    $patientAppointmentId = (int) ($patientAppointment['data']['id'] ?? 0);
    assertTrue($patientAppointmentId > 0, 'patient-created appointment ID was not returned');
    expectResponse('Linked patient is listed', requestApi($baseUrl, 'GET', '/api/patients', null, $professionalToken), 200, static function (mixed $payload) use ($patientId): void {
        assertTrue(listContainsId($payload, $patientId), 'patient who booked was not listed for the professional');
    });
    // Linked by the patient's booking, but not registered by this professional:
    // generating a link here would hand the professional the patient's account.
    expectResponse('Activation: linked self-registered account cannot be taken over', requestApi($baseUrl, 'POST', "/api/patients/{$patientId}/activation-link", [], $professionalToken), 403);

    expectResponse('Appointment rejects an impossible date', requestApi($baseUrl, 'POST', '/api/appointments', [
        'patient_id' => $patientId,
        'scheduled_at' => '2031-02-30 10:00',
    ], $professionalToken), 422);
    expectResponse('Appointment rejects a procedure from another professional', requestApi($baseUrl, 'POST', '/api/appointments', [
        'patient_id' => $patientId,
        'procedure_id' => 999999999,
        'scheduled_at' => '2031-08-20 10:30',
    ], $professionalToken), 422);
    expectResponse('Patient cannot attach a foreign procedure', requestApi($baseUrl, 'PUT', "/api/appointments/{$patientAppointmentId}", [
        'procedure_id' => 999999999,
        'scheduled_at' => '2031-08-21 10:00:00',
    ], $patientToken), 422);

    // datetime-local input ("T" separator, no seconds) is stored normalized.
    $professionalAppointment = expectResponse('Professional appointment create', requestApi($baseUrl, 'POST', '/api/appointments', [
        'patient_id' => $patientId,
        'procedure_id' => $procedureId,
        'scheduled_at' => '2031-08-20T10:30',
        'notes' => 'Agendamento temporário E2E.',
    ], $professionalToken), 201, static function (mixed $payload): void {
        assertTrue(($payload['data']['scheduled_at'] ?? null) === '2031-08-20 10:30:00', 'scheduled_at was not normalized');
    });
    $professionalAppointmentId = (int) ($professionalAppointment['data']['id'] ?? 0);
    assertTrue($professionalAppointmentId > 0, 'appointment ID was not returned');
    expectResponse('Professional appointment update', requestApi($baseUrl, 'PUT', "/api/appointments/{$professionalAppointmentId}", [
        'procedure_id' => $procedureId,
        'scheduled_at' => '2031-08-20 11:00:00',
        'notes' => 'Agendamento temporário atualizado.',
    ], $professionalToken), 200);
    expectResponse('Professional appointment status update', requestApi($baseUrl, 'PATCH', "/api/appointments/{$professionalAppointmentId}/status", ['status' => 'confirmed'], $professionalToken), 200);
    expectResponse('Conflict: overlapping appointment is refused', requestApi($baseUrl, 'POST', '/api/appointments', [
        'patient_id' => $patientId,
        'scheduled_at' => '2031-08-20 11:30',
    ], $professionalToken), 409);
    expectResponse('Conflict: earlier appointment running into the slot is refused', requestApi($baseUrl, 'POST', '/api/appointments', [
        'patient_id' => $patientId,
        'procedure_id' => $procedureId,
        'scheduled_at' => '2031-08-20 10:30',
    ], $professionalToken), 409);
    $adjacent = expectResponse('Conflict: back-to-back appointment is allowed', requestApi($baseUrl, 'POST', '/api/appointments', [
        'patient_id' => $patientId,
        'scheduled_at' => '2031-08-20 11:45',
    ], $professionalToken), 201);
    $adjacentId = (int) ($adjacent['data']['id'] ?? 0);
    expectResponse('Conflict: moving into an occupied slot is refused', requestApi($baseUrl, 'PUT', "/api/appointments/{$adjacentId}", [
        'scheduled_at' => '2031-08-20 11:15',
    ], $professionalToken), 409);
    expectResponse('Conflict: back-to-back appointment delete', requestApi($baseUrl, 'DELETE', "/api/appointments/{$adjacentId}", null, $professionalToken), 204);
    expectResponse('Patient generic appointment list', requestApi($baseUrl, 'GET', '/api/appointments', null, $patientToken), 200, static function (mixed $payload) use ($professionalAppointmentId): void {
        assertTrue(listContainsId($payload, $professionalAppointmentId), 'patient cannot see own appointment in generic list');
    });
    expectResponse('Patient cannot confirm appointment', requestApi($baseUrl, 'PATCH', "/api/appointments/{$professionalAppointmentId}/status", ['status' => 'confirmed'], $patientToken), 403);
    expectResponse('Patient appointment cancellation', requestApi($baseUrl, 'PATCH', "/api/appointments/{$professionalAppointmentId}/status", ['status' => 'canceled'], $patientToken), 200);
    $rebooked = expectResponse('Conflict: canceled slot can be booked again', requestApi($baseUrl, 'POST', '/api/appointments', [
        'patient_id' => $patientId,
        'scheduled_at' => '2031-08-20 11:00',
    ], $professionalToken), 201);
    expectResponse('Conflict: reactivating a canceled appointment over a new one is refused', requestApi($baseUrl, 'PATCH', "/api/appointments/{$professionalAppointmentId}/status", ['status' => 'scheduled'], $professionalToken), 409);
    $rebookedId = (int) ($rebooked['data']['id'] ?? 0);
    expectResponse('Lifecycle: professional completes the appointment', requestApi($baseUrl, 'PATCH', "/api/appointments/{$rebookedId}/status", ['status' => 'completed'], $professionalToken), 200);
    expectResponse('Lifecycle: patient cannot cancel a completed appointment', requestApi($baseUrl, 'PATCH', "/api/appointments/{$rebookedId}/status", ['status' => 'canceled'], $patientToken), 422);
    expectResponse('Lifecycle: completed appointment cannot be rescheduled', requestApi($baseUrl, 'PUT', "/api/appointments/{$rebookedId}", ['scheduled_at' => '2031-08-25 10:00'], $professionalToken), 422);
    expectResponse('Lifecycle: patient cannot delete appointments', requestApi($baseUrl, 'DELETE', "/api/appointments/{$rebookedId}", null, $patientToken), 403);
    expectResponse('Lifecycle: completed appointment cannot be deleted', requestApi($baseUrl, 'DELETE', "/api/appointments/{$rebookedId}", null, $professionalToken), 422);

    expectResponse('Professional patient show after appointment', requestApi($baseUrl, 'GET', "/api/patients/{$patientId}", null, $professionalToken), 200);
    expectResponse('Professional patient record list', requestApi($baseUrl, 'GET', "/api/patients/{$patientId}/records", null, $professionalToken), 200);
    $audit = $database->prepare("SELECT COUNT(*) FROM audit_logs WHERE action = 'medical_records.viewed' AND patient_id = ? AND actor_user_id IS NOT NULL AND ip IS NOT NULL");
    $audit->execute([$patientId]);
    $auditCount = (int) $audit->fetchColumn();
    $audit->closeCursor();
    assertTrue($auditCount >= 1, 'record access was not audited');
    $newRecord = expectResponse('Professional medical record create', requestApi($baseUrl, 'POST', "/api/patients/{$patientId}/records", [
        'appointment_id' => $professionalAppointmentId,
        'clinical_notes' => 'Registro clínico temporário E2E.',
        'prescription' => 'Orientação temporária E2E.',
    ], $professionalToken), 201);
    $recordId = (int) ($newRecord['data']['id'] ?? 0);
    assertTrue($recordId > 0, 'medical record ID was not returned');
    expectResponse('Patient sees own new medical record', requestApi($baseUrl, 'GET', '/api/patient/medical-records', null, $patientToken), 200, static function (mixed $payload) use ($recordId): void {
        assertTrue(listContainsId($payload, $recordId), 'patient cannot see own new medical record');
    });

    expectResponse('Patient cancels instead of deleting', requestApi($baseUrl, 'PATCH', "/api/appointments/{$patientAppointmentId}/status", ['status' => 'canceled'], $patientToken), 200);
    expectResponse('Professional deletes the canceled request', requestApi($baseUrl, 'DELETE', "/api/appointments/{$patientAppointmentId}", null, $professionalToken), 204);
    expectResponse('Professional appointment delete', requestApi($baseUrl, 'DELETE', "/api/appointments/{$professionalAppointmentId}", null, $professionalToken), 204);

    $managedEmail = "e2e-managed-{$suffix}@example.test";
    $testEmails[] = $managedEmail;
    $managedPatient = expectResponse('Professional patient create', requestApi($baseUrl, 'POST', '/api/patients', [
        'name' => 'Paciente gerenciado E2E ' . $suffix,
        'cpf' => '111.222.333-44',
        'phone' => '11977770000',
        'email' => $managedEmail,
        'birth_date' => '1985-05-15',
        'allergies' => 'Nenhuma',
        'notes' => 'Cadastro temporário E2E.',
    ], $professionalToken), 201);
    $managedPatientId = (int) ($managedPatient['data']['id'] ?? 0);
    assertTrue($managedPatientId > 0, 'managed patient ID was not returned');
    expectResponse('Patient create rejects an e-mail already in use', requestApi($baseUrl, 'POST', '/api/patients', [
        'name' => 'Paciente duplicado E2E ' . $suffix,
        'cpf' => '555.666.777-88',
        'phone' => '11966660000',
        'email' => $managedEmail,
    ], $professionalToken), 422);
    $duplicate = $database->prepare("SELECT COUNT(*) FROM users WHERE name = ?");
    $duplicate->execute(['Paciente duplicado E2E ' . $suffix]);
    $duplicateCount = (int) $duplicate->fetchColumn();
    $duplicate->closeCursor();
    assertTrue($duplicateCount === 0, 'rejected duplicate patient still created a user');
    expectResponse('Professional patient list', requestApi($baseUrl, 'GET', '/api/patients', null, $professionalToken), 200, static function (mixed $payload) use ($managedPatientId): void {
        assertTrue(listContainsId($payload, $managedPatientId), 'created patient was not returned in the list');
    });
    expectResponse('Professional patient search', requestApi($baseUrl, 'GET', '/api/patients/search?q=gerenciado', null, $professionalToken), 200, static function (mixed $payload) use ($managedPatientId): void {
        assertTrue(listContainsId($payload, $managedPatientId), 'created patient was not returned in search');
    });
    expectResponse('Professional managed patient show', requestApi($baseUrl, 'GET', "/api/patients/{$managedPatientId}", null, $professionalToken), 200);

    $secondProfessionalEmail = "e2e-professional-{$suffix}@example.test";
    $secondProfessionalPassword = "Nuva-Pro-{$suffix}!";
    $testEmails[] = $secondProfessionalEmail;
    expectResponse('Public registration cannot create a professional', requestApi($baseUrl, 'POST', '/api/register', [
        'name' => 'Profissional E2E ' . $suffix,
        'email' => $secondProfessionalEmail,
        'password' => $secondProfessionalPassword,
        'password_confirmation' => $secondProfessionalPassword,
        'role' => 'professional',
    ]), 403);
    $leftover = $database->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
    $leftover->execute([$secondProfessionalEmail]);
    $leftoverCount = (int) $leftover->fetchColumn();
    $leftover->closeCursor(); // an open SQLite cursor would block the server's writes
    assertTrue($leftoverCount === 0, 'refused professional registration still created a user');

    // Professionals are provisioned by the clinic (scripts/create-professional.php).
    // The test provisions one directly and issues a token without using /api/login,
    // so the login rate-limit count below stays unchanged.
    $database->prepare("INSERT INTO users (name, email, password, role, created_at, updated_at) VALUES (?, ?, ?, 'professional', datetime('now'), datetime('now'))")
        ->execute(['Profissional E2E ' . $suffix, $secondProfessionalEmail, password_hash($secondProfessionalPassword, PASSWORD_BCRYPT)]);
    $secondProfessionalUserId = (int) $database->lastInsertId();
    $database->prepare("INSERT INTO professionals (user_id, specialty, created_at, updated_at) VALUES (?, 'Clínico Geral', datetime('now'), datetime('now'))")
        ->execute([$secondProfessionalUserId]);
    $secondProfessionalToken = bin2hex(random_bytes(32));
    $database->prepare("INSERT INTO personal_access_tokens (user_id, token, name, created_at, expires_at, last_used_at) VALUES (?, ?, 'auth_token', ?, ?, ?)")
        ->execute([$secondProfessionalUserId, hash('sha256', $secondProfessionalToken), clinicNow(), clinicNow('+12 hours'), clinicNow()]);
    expectResponse('BOLA: second professional cannot show another patient', requestApi($baseUrl, 'GET', "/api/patients/{$managedPatientId}", null, $secondProfessionalToken), 403);
    expectResponse('BOLA: second professional cannot list another record', requestApi($baseUrl, 'GET', "/api/patients/{$managedPatientId}/records", null, $secondProfessionalToken), 403);
    expectResponse('BOLA: second professional cannot book another patient to gain access', requestApi($baseUrl, 'POST', '/api/appointments', [
        'patient_id' => $managedPatientId,
        'scheduled_at' => '2031-08-22 10:00:00',
    ], $secondProfessionalToken), 403);
    expectResponse('BOLA: records stay protected after the booking attempt', requestApi($baseUrl, 'GET', "/api/patients/{$managedPatientId}/records", null, $secondProfessionalToken), 403);
    expectResponse('BOLA: second professional lists no foreign patients', requestApi($baseUrl, 'GET', '/api/patients', null, $secondProfessionalToken), 200, static function (mixed $payload) use ($managedPatientId, $patientId): void {
        assertTrue(!listContainsId($payload, $managedPatientId) && !listContainsId($payload, $patientId), 'foreign patients were listed');
    });
    expectResponse('BOLA: second professional cannot search foreign patients', requestApi($baseUrl, 'GET', '/api/patients/search?q=E2E', null, $secondProfessionalToken), 200, static function (mixed $payload) use ($managedPatientId, $patientId): void {
        assertTrue(!listContainsId($payload, $managedPatientId) && !listContainsId($payload, $patientId), 'foreign patients were returned in search');
    });
    expectResponse('BOLA: second professional cannot create another record', requestApi($baseUrl, 'POST', "/api/patients/{$managedPatientId}/records", [
        'clinical_notes' => 'Tentativa indevida.',
    ], $secondProfessionalToken), 403);

    expectResponse('Activation: managed patient starts pending', requestApi($baseUrl, 'GET', "/api/patients/{$managedPatientId}", null, $professionalToken), 200, static function (mixed $payload): void {
        assertTrue(($payload['data']['portal_status'] ?? null) === 'pending', 'managed patient is not pending');
        assertTrue(($payload['data']['can_generate_activation_link'] ?? false) === true, 'registering professional cannot generate the link');
    });
    expectResponse('Activation: unrelated professional cannot generate a link', requestApi($baseUrl, 'POST', "/api/patients/{$managedPatientId}/activation-link", [], $secondProfessionalToken), 404);
    $activationLink = expectResponse('Activation: registering professional generates a link', requestApi($baseUrl, 'POST', "/api/patients/{$managedPatientId}/activation-link", ['email' => $managedEmail], $professionalToken), 200, static function (mixed $payload): void {
        assertTrue(str_starts_with((string) ($payload['data']['activation_path'] ?? ''), '/ativar-conta?'), 'activation path was not returned');
    });
    parse_str((string) parse_url($activationLink['data']['activation_path'], PHP_URL_QUERY), $linkQuery);
    $managedPassword = "Nuva-Managed-{$suffix}!";
    expectResponse('Activation: patient sets a password with the link', requestApi($baseUrl, 'POST', '/api/activate-account', [
        'email' => $linkQuery['email'] ?? '',
        'token' => $linkQuery['token'] ?? '',
        'password' => $managedPassword,
        'password_confirmation' => $managedPassword,
    ]), 200);
    expectResponse('Activation: link works only once', requestApi($baseUrl, 'POST', '/api/activate-account', [
        'email' => $linkQuery['email'] ?? '',
        'token' => $linkQuery['token'] ?? '',
        'password' => $managedPassword,
        'password_confirmation' => $managedPassword,
    ]), 400);
    expectResponse('Activation: account is now active', requestApi($baseUrl, 'GET', "/api/patients/{$managedPatientId}", null, $professionalToken), 200, static function (mixed $payload): void {
        assertTrue(($payload['data']['portal_status'] ?? null) === 'active', 'activated account is not active');
    });
    expectResponse('Activation: active account cannot get a new link', requestApi($baseUrl, 'POST', "/api/patients/{$managedPatientId}/activation-link", [], $professionalToken), 409);

    $noEmailPatient = expectResponse('Activation: patient registered without e-mail', requestApi($baseUrl, 'POST', '/api/patients', [
        'name' => 'Paciente sem e-mail E2E ' . $suffix,
        'cpf' => '999.888.777-66',
        'phone' => '11955550000',
    ], $professionalToken), 201, static function (mixed $payload): void {
        assertTrue(array_key_exists('email', $payload['data'] ?? []) && $payload['data']['email'] === null, 'placeholder e-mail was exposed');
    });
    $noEmailPatientId = (int) ($noEmailPatient['data']['id'] ?? 0);
    // The API hides the internal placeholder address; register it for cleanup.
    $placeholder = $database->prepare('SELECT u.email FROM users u JOIN patients p ON p.user_id = u.id WHERE p.id = ?');
    $placeholder->execute([$noEmailPatientId]);
    $testEmails[] = (string) $placeholder->fetchColumn();
    $placeholder->closeCursor();
    expectResponse('Activation: link requires an e-mail', requestApi($baseUrl, 'POST', "/api/patients/{$noEmailPatientId}/activation-link", [], $professionalToken), 422);
    $noEmailAddress = "e2e-noemail-{$suffix}@example.test";
    $testEmails[] = $noEmailAddress;
    expectResponse('Activation: e-mail is set when generating the link', requestApi($baseUrl, 'POST', "/api/patients/{$noEmailPatientId}/activation-link", ['email' => $noEmailAddress], $professionalToken), 200, static function (mixed $payload) use ($noEmailAddress): void {
        assertTrue(($payload['data']['email'] ?? null) === $noEmailAddress, 'patient e-mail was not updated');
    });

    expectResponse('Procedure delete', requestApi($baseUrl, 'DELETE', "/api/procedures/{$procedureId}", null, $professionalToken), 204);
    $procedureId = null;
    expectResponse('Patient logout', requestApi($baseUrl, 'POST', '/api/logout', [], $patientToken), 200);
    expectResponse('Revoked patient token is rejected', requestApi($baseUrl, 'GET', '/api/user', null, $patientToken), 401);
    expectResponse('Professional logout', requestApi($baseUrl, 'POST', '/api/logout', [], $professionalToken), 200);
    expectResponse('Second professional logout', requestApi($baseUrl, 'POST', '/api/logout', [], $secondProfessionalToken), 200);

    // Login has exactly three valid calls above. Eight invalid attempts exercise
    // the 10/minute limiter without affecting any preceding test.
    for ($attempt = 1; $attempt <= 7; $attempt++) {
        expectResponse("Rate limit pre-threshold attempt {$attempt}", requestApi($baseUrl, 'POST', '/api/login', [
            'email' => 'missing-' . $suffix . '@example.test',
            'password' => 'incorrect-password',
        ]), 401);
    }
    expectResponse('Login rate limit', requestApi($baseUrl, 'POST', '/api/login', [
        'email' => 'missing-' . $suffix . '@example.test',
        'password' => 'incorrect-password',
    ]), 429);
} catch (Throwable $error) {
    if ($failed === 0) {
        $failed++;
    }
    fwrite(STDERR, "\nE2E stopped: {$error->getMessage()}\n");
} finally {
    cleanupTestData($database, $testEmails, $procedureId);
    resetLocalLoginRateLimit($database);
}

$total = $passed + $failed;
echo "\nResult: {$passed}/{$total} passed";
if ($failed > 0) {
    echo ", {$failed} failed.\n";
    exit(1);
}

echo ".\n";
