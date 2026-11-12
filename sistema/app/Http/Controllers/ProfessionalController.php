<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Clinical\ProfessionalQueries;
use App\Domain\Shared\NotFound;
use App\Http\Request;
use App\Http\Response;

final class ProfessionalController
{
    public function __construct(private readonly ProfessionalQueries $professionals)
    {
    }

    public function dashboard(Request $request): Response
    {
        $professionalId = $request->actor()->professionalId;
        $dashboard = $professionalId !== null ? $this->professionals->dashboard($professionalId) : null;

        return Response::json($dashboard ?? throw new NotFound('Profissional não encontrado.'));
    }

    public function search(Request $request): Response
    {
        $term = trim((string) $request->query('q', ''));

        return Response::json(mb_strlen($term) < 2 ? [] : $this->professionals->search(mb_substr($term, 0, 100)));
    }
}
