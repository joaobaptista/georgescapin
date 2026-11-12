<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Catalog\ManageProcedures;
use App\Application\Catalog\ProcedureQueries;
use App\Http\Request;
use App\Http\Response;
use App\Http\Validation\Validator;

final class ProcedureController
{
    private const FIELDS = [
        'category' => 'nullable|string|max:255',
        'description' => 'nullable|string|max:2000',
        'base_price' => 'nullable|numeric|min:0',
        'duration_minutes' => 'nullable|integer|min:1|max:1440',
    ];

    public function __construct(
        private readonly ManageProcedures $procedures,
        private readonly ProcedureQueries $catalog,
    ) {
    }

    public function index(Request $request): Response
    {
        $professionalId = $request->actor()->professionalId;

        return Response::json($professionalId !== null ? $this->catalog->ownedBy($professionalId) : []);
    }

    public function show(Request $request): Response
    {
        $id = $request->routeInt('procedure');
        $this->procedures->assertVisible($request->actor(), $id);

        return Response::json($this->catalog->find($id));
    }

    public function store(Request $request): Response
    {
        $input = Validator::validate($request->all(), ['name' => 'required|string|max:255'] + self::FIELDS);
        $id = $this->procedures->create($request->actor(), $input);

        return Response::json($this->catalog->find($id), 201);
    }

    public function update(Request $request): Response
    {
        $id = $request->routeInt('procedure');
        $input = Validator::validate($request->all(), ['name' => 'sometimes|required|string|max:255'] + self::FIELDS);
        // Only fields actually sent are changed.
        $this->procedures->update($request->actor(), $id, array_intersect_key($input, $request->all()));

        return Response::json($this->catalog->find($id));
    }

    public function destroy(Request $request): Response
    {
        $this->procedures->remove($request->actor(), $request->routeInt('procedure'));

        return Response::noContent();
    }
}
