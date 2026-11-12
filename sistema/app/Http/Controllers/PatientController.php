<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Clinical\GenerateActivationLink;
use App\Application\Clinical\PatientQueries;
use App\Application\Clinical\RegisterPatient;
use App\Application\Clinical\ShowPatient;
use App\Http\Request;
use App\Http\Response;
use App\Http\Validation\Validator;

final class PatientController
{
    public function __construct(
        private readonly RegisterPatient $register,
        private readonly ShowPatient $show,
        private readonly GenerateActivationLink $activationLink,
        private readonly PatientQueries $patients,
    ) {
    }

    public function index(Request $request): Response
    {
        $professionalId = $request->actor()->professionalIdOrFail('Apenas profissionais de saúde têm acesso à listagem de pacientes.');

        return Response::json(['data' => $this->patients->linkedTo($professionalId)]);
    }

    public function search(Request $request): Response
    {
        $professionalId = $request->actor()->professionalIdOrFail('Apenas profissionais de saúde podem buscar pacientes.');
        $term = trim((string) $request->query('q', ''));

        return Response::json(['data' => $term === '' ? [] : $this->patients->searchLinkedTo($professionalId, mb_substr($term, 0, 100))]);
    }

    public function show(Request $request): Response
    {
        return Response::json(['data' => ($this->show)($request->actor(), $request->routeInt('patient'))]);
    }

    public function store(Request $request): Response
    {
        $input = Validator::validate($request->all(), [
            'name' => 'required|string|max:255',
            'cpf' => 'required|string|max:20',
            'birth_date' => 'nullable|string|max:10',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'allergies' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:2000',
        ]);
        $patientId = ($this->register)($request->actor(), $input);

        return Response::json(['data' => $this->patients->find($patientId)], 201);
    }

    public function activationLink(Request $request): Response
    {
        $input = Validator::validate($request->all(), ['email' => 'nullable|email|max:255']);

        return Response::json(['data' => ($this->activationLink)($request->actor(), $request->routeInt('id'), $input['email'] ?? null)]);
    }
}
