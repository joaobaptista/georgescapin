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
function resetLocalLoginRateLimit(): void
{
    $file = sys_get_temp_dir() . '/nuva_rate_limits/limit_' . md5('127.0.0.1_/api/login') . '.json';
    if (is_file($file)) {
        @unlink($file);
    }
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
    resetLocalLoginRateLimit();

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
    $database->prepare("INSERT INTO password_reset_tokens (email, token, created_at) VALUES (?, ?, datetime('now'))")
        ->execute([$registeredEmail, $activationToken]);
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

    $scheduledAt = '2031-08-20 10:30:00';
    $professionalAppointment = expectResponse('Professional appointment create', requestApi($baseUrl, 'POST', '/api/appointments', [
        'patient_id' => $patientId,
        'procedure_id' => $procedureId,
        'scheduled_at' => $scheduledAt,
        'notes' => 'Agendamento temporário E2E.',
    ], $professionalToken), 201);
    $professionalAppointmentId = (int) ($professionalAppointment['data']['id'] ?? 0);
    assertTrue($professionalAppointmentId > 0, 'appointment ID was not returned');
    expectResponse('Professional appointment update', requestApi($baseUrl, 'PUT', "/api/appointments/{$professionalAppointmentId}", [
        'procedure_id' => $procedureId,
        'scheduled_at' => '2031-08-20 11:00:00',
        'notes' => 'Agendamento temporário atualizado.',
    ], $professionalToken), 200);
    expectResponse('Professional appointment status update', requestApi($baseUrl, 'PATCH', "/api/appointments/{$professionalAppointmentId}/status", ['status' => 'confirmed'], $professionalToken), 200);
    expectResponse('Patient generic appointment list', requestApi($baseUrl, 'GET', '/api/appointments', null, $patientToken), 200, static function (mixed $payload) use ($professionalAppointmentId): void {
        assertTrue(listContainsId($payload, $professionalAppointmentId), 'patient cannot see own appointment in generic list');
    });
    expectResponse('Patient cannot confirm appointment', requestApi($baseUrl, 'PATCH', "/api/appointments/{$professionalAppointmentId}/status", ['status' => 'confirmed'], $patientToken), 403);
    expectResponse('Patient appointment cancellation', requestApi($baseUrl, 'PATCH', "/api/appointments/{$professionalAppointmentId}/status", ['status' => 'canceled'], $patientToken), 200);

    expectResponse('Professional patient show after appointment', requestApi($baseUrl, 'GET', "/api/patients/{$patientId}", null, $professionalToken), 200);
    expectResponse('Professional patient record list', requestApi($baseUrl, 'GET', "/api/patients/{$patientId}/records", null, $professionalToken), 200);
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

    $patientAppointment = expectResponse('Patient appointment create', requestApi($baseUrl, 'POST', '/api/appointments', [
        'professional_id' => $professionalId,
        'scheduled_at' => '2031-08-21 10:00:00',
        'notes' => 'Solicitação temporária feita pelo paciente.',
    ], $patientToken), 201);
    $patientAppointmentId = (int) ($patientAppointment['data']['id'] ?? 0);
    assertTrue($patientAppointmentId > 0, 'patient-created appointment ID was not returned');
    expectResponse('Patient appointment delete', requestApi($baseUrl, 'DELETE', "/api/appointments/{$patientAppointmentId}", null, $patientToken), 204);
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
    $secondProfessional = expectResponse('Second professional registration', requestApi($baseUrl, 'POST', '/api/register', [
        'name' => 'Profissional E2E ' . $suffix,
        'email' => $secondProfessionalEmail,
        'password' => $secondProfessionalPassword,
        'password_confirmation' => $secondProfessionalPassword,
        'role' => 'professional',
    ]), 201);
    $secondProfessionalToken = $secondProfessional['token'];
    expectResponse('BOLA: second professional cannot show another patient', requestApi($baseUrl, 'GET', "/api/patients/{$managedPatientId}", null, $secondProfessionalToken), 403);
    expectResponse('BOLA: second professional cannot list another record', requestApi($baseUrl, 'GET', "/api/patients/{$managedPatientId}/records", null, $secondProfessionalToken), 403);
    expectResponse('BOLA: second professional cannot create another record', requestApi($baseUrl, 'POST', "/api/patients/{$managedPatientId}/records", [
        'clinical_notes' => 'Tentativa indevida.',
    ], $secondProfessionalToken), 403);

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
    resetLocalLoginRateLimit();
}

$total = $passed + $failed;
echo "\nResult: {$passed}/{$total} passed";
if ($failed > 0) {
    echo ", {$failed} failed.\n";
    exit(1);
}

echo ".\n";
