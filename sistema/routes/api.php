<?php

use App\Controllers\AuthController;
use App\Controllers\AppointmentController;
use App\Controllers\MedicalRecordController;
use App\Controllers\PatientController;
use App\Controllers\ProcedureController;
use App\Controllers\ProfessionalController;
use App\Core\Router;

/** @var Router $router */

// Autenticação pública
$router->post('/api/register', [AuthController::class, 'register'], ['rate_limit:10,1']);
$router->post('/api/login', [AuthController::class, 'login'], ['rate_limit:10,1']);
$router->post('/api/activate-account', [AuthController::class, 'activateAccount'], ['rate_limit:10,1']);

// Busca pública de profissionais
$router->get('/api/professionals/search', [ProfessionalController::class, 'search']);

// Rotas Autenticadas (Bearer Token)
$router->group(['auth'], function (Router $r) {
    // Usuário logado e perfil comum
    $r->get('/api/user', [AuthController::class, 'user']);
    $r->put('/api/user/profile', [AuthController::class, 'updateProfile']);
    $r->put('/api/user/password', [AuthController::class, 'updatePassword']);
    $r->post('/api/logout', [AuthController::class, 'logout']);

    // Agendamentos (com validação interna de escopo nos métodos)
    $r->get('/api/appointments', [AppointmentController::class, 'index']);
    $r->post('/api/appointments', [AppointmentController::class, 'store']);
    $r->put('/api/appointments/{appointment}', [AppointmentController::class, 'update']);
    $r->patch('/api/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus']);
    $r->delete('/api/appointments/{appointment}', [AppointmentController::class, 'destroy']);

    // Portal do Paciente
    $r->group(['role:patient'], function (Router $pat) {
        $pat->get('/api/patient/appointments', [AppointmentController::class, 'patientAppointments']);
        $pat->get('/api/patient/medical-records', [MedicalRecordController::class, 'patientRecords']);
    });

    // Portal do Profissional / Gestão Médica
    $r->group(['role:professional'], function (Router $pro) {
        $pro->get('/api/professional/dashboard', [ProfessionalController::class, 'dashboard']);
        
        // Procedimentos
        $pro->get('/api/procedures', [ProcedureController::class, 'index']);
        $pro->post('/api/procedures', [ProcedureController::class, 'store']);
        $pro->get('/api/procedures/{procedure}', [ProcedureController::class, 'show']);
        $pro->put('/api/procedures/{procedure}', [ProcedureController::class, 'update']);
        $pro->delete('/api/procedures/{procedure}', [ProcedureController::class, 'destroy']);

        // Gestão de Pacientes e Prontuários Médicos
        $pro->get('/api/patients/search', [PatientController::class, 'search']);
        $pro->get('/api/patients', [PatientController::class, 'index']);
        $pro->post('/api/patients', [PatientController::class, 'store']);
        $pro->get('/api/patients/{patient}', [PatientController::class, 'show']);
        $pro->get('/api/patients/{id}/records', [MedicalRecordController::class, 'recordsForPatient']);
        $pro->post('/api/patients/{id}/records', [MedicalRecordController::class, 'storeRecord']);
    });
});
