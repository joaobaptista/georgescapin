<?php

declare(strict_types=1);

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProcedureController;
use App\Http\Controllers\ProfessionalController;
use App\Http\Router;

/** @var Router $router */

// Autenticação pública
$router->post('/api/register', [AuthController::class, 'register'], ['rate_limit:10,1']);
$router->post('/api/login', [AuthController::class, 'login'], ['rate_limit:10,1']);
$router->post('/api/activate-account', [AuthController::class, 'activateAccount'], ['rate_limit:10,1']);

// Entrar / cadastrar com o Google (OpenID Connect + PKCE)
$router->get('/api/auth/providers', [GoogleAuthController::class, 'providers']);
$router->get('/api/auth/google/redirect', [GoogleAuthController::class, 'redirect'], ['rate_limit:20,1']);
$router->get('/api/auth/google/callback', [GoogleAuthController::class, 'callback'], ['rate_limit:20,1']);
$router->post('/api/auth/google/exchange', [GoogleAuthController::class, 'exchange'], ['rate_limit:20,1']);

// Busca pública de profissionais
$router->get('/api/professionals/search', [ProfessionalController::class, 'search'], ['rate_limit:60,1']);

// Rotas autenticadas (Bearer token)
$router->group(['auth'], function (Router $r): void {
    $r->get('/api/user', [AuthController::class, 'user']);
    $r->put('/api/user/profile', [AuthController::class, 'updateProfile']);
    $r->put('/api/user/password', [AuthController::class, 'updatePassword'], ['rate_limit:10,1']);
    $r->post('/api/logout', [AuthController::class, 'logout']);

    // Agendamentos: o domínio decide o que cada lado (paciente/profissional) pode fazer.
    $r->get('/api/appointments', [AppointmentController::class, 'index']);
    $r->post('/api/appointments', [AppointmentController::class, 'store']);
    $r->put('/api/appointments/{appointment}', [AppointmentController::class, 'update']);
    $r->patch('/api/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus']);
    $r->delete('/api/appointments/{appointment}', [AppointmentController::class, 'destroy']);

    // Portal do paciente
    $r->group(['role:patient'], function (Router $patient): void {
        $patient->get('/api/patient/appointments', [AppointmentController::class, 'patientAppointments']);
        $patient->get('/api/patient/medical-records', [MedicalRecordController::class, 'patientRecords']);
    });

    // Portal do profissional
    $r->group(['role:professional'], function (Router $pro): void {
        $pro->get('/api/professional/dashboard', [ProfessionalController::class, 'dashboard']);

        $pro->get('/api/procedures', [ProcedureController::class, 'index']);
        $pro->post('/api/procedures', [ProcedureController::class, 'store']);
        $pro->get('/api/procedures/{procedure}', [ProcedureController::class, 'show']);
        $pro->put('/api/procedures/{procedure}', [ProcedureController::class, 'update']);
        $pro->delete('/api/procedures/{procedure}', [ProcedureController::class, 'destroy']);

        $pro->get('/api/patients/search', [PatientController::class, 'search']);
        $pro->get('/api/patients', [PatientController::class, 'index']);
        $pro->post('/api/patients', [PatientController::class, 'store']);
        $pro->get('/api/patients/{patient}', [PatientController::class, 'show']);
        $pro->get('/api/patients/{id}/records', [MedicalRecordController::class, 'recordsForPatient']);
        $pro->post('/api/patients/{id}/records', [MedicalRecordController::class, 'storeRecord']);
        $pro->post('/api/patients/{id}/activation-link', [PatientController::class, 'activationLink'], ['rate_limit:30,1']);
    });
});
