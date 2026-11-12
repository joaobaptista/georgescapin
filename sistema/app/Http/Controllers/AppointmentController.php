<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Scheduling\AppointmentQueries;
use App\Application\Scheduling\ChangeAppointmentStatus;
use App\Application\Scheduling\RemoveAppointment;
use App\Application\Scheduling\RescheduleAppointment;
use App\Application\Scheduling\ScheduleAppointment;
use App\Domain\Identity\Role;
use App\Domain\Scheduling\AppointmentStatus;
use App\Http\Request;
use App\Http\Response;
use App\Http\Validation\Validator;

final class AppointmentController
{
    private const DETAILS = [
        'procedure_id' => 'nullable|integer',
        'scheduled_at' => 'required|string|max:25',
        'notes' => 'nullable|string|max:2000',
    ];

    public function __construct(
        private readonly ScheduleAppointment $schedule,
        private readonly RescheduleAppointment $reschedule,
        private readonly ChangeAppointmentStatus $changeStatus,
        private readonly RemoveAppointment $remove,
        private readonly AppointmentQueries $appointments,
    ) {
    }

    public function index(Request $request): Response
    {
        $actor = $request->actor();
        $data = match (true) {
            $actor->professionalId !== null => $this->appointments->forProfessional($actor->professionalId),
            $actor->patientId !== null => $this->appointments->forPatient($actor->patientId),
            default => [],
        };

        return Response::json(['data' => $data]);
    }

    public function patientAppointments(Request $request): Response
    {
        $patientId = $request->actor()->patientId;

        return Response::json(['data' => $patientId !== null ? $this->appointments->forPatient($patientId) : []]);
    }

    public function store(Request $request): Response
    {
        $counterpart = $request->actor()->role === Role::Professional ? 'patient_id' : 'professional_id';
        $input = Validator::validate($request->all(), self::DETAILS + [$counterpart => 'required|integer']);
        $id = ($this->schedule)($request->actor(), $input);

        return Response::json(['data' => $this->appointments->find($id)], 201);
    }

    public function update(Request $request): Response
    {
        $id = $request->routeInt('appointment');
        ($this->reschedule)($request->actor(), $id, Validator::validate($request->all(), self::DETAILS));

        return Response::json(['data' => $this->appointments->find($id)]);
    }

    public function updateStatus(Request $request): Response
    {
        $id = $request->routeInt('appointment');
        $input = Validator::validate($request->all(), ['status' => 'required|in:scheduled,confirmed,canceled,completed']);
        ($this->changeStatus)($request->actor(), $id, AppointmentStatus::from($input['status']));

        return Response::json(['data' => $this->appointments->find($id)]);
    }

    public function destroy(Request $request): Response
    {
        ($this->remove)($request->actor(), $request->routeInt('appointment'));

        return Response::noContent();
    }
}
