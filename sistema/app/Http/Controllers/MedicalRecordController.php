<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Clinical\AddMedicalRecord;
use App\Application\Clinical\MedicalRecordQueries;
use App\Application\Clinical\ViewPatientRecords;
use App\Http\Request;
use App\Http\Response;
use App\Http\Validation\Validator;

final class MedicalRecordController
{
    public function __construct(
        private readonly ViewPatientRecords $view,
        private readonly AddMedicalRecord $add,
        private readonly MedicalRecordQueries $records,
    ) {
    }

    public function recordsForPatient(Request $request): Response
    {
        return Response::json(['data' => ($this->view)($request->actor(), $request->routeInt('id'))]);
    }

    public function storeRecord(Request $request): Response
    {
        $input = Validator::validate($request->all(), [
            'clinical_notes' => 'nullable|string|max:10000',
            'prescription' => 'nullable|string|max:5000',
            'appointment_id' => 'nullable|integer',
        ]);
        $recordId = ($this->add)($request->actor(), $request->routeInt('id'), $input);

        return Response::json(['data' => $this->records->find($recordId)], 201);
    }

    /** The patient's own record (patients are not audited reading their own data). */
    public function patientRecords(Request $request): Response
    {
        $patientId = $request->actor()->patientId;

        return Response::json(['data' => $patientId !== null ? $this->records->forPatient($patientId) : []]);
    }
}
