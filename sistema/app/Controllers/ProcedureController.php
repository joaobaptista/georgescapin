<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;

class ProcedureController
{
    public function index(Request $request): void
    {
        $professionalId = $request->user['professional']['id'] ?? null;
        
        if ($professionalId) {
            $sql = "SELECT * FROM procedures WHERE professional_id = ? OR professional_id IS NULL ORDER BY name ASC";
            $procedures = Database::fetchAll($sql, [$professionalId]);
        } else {
            $sql = "SELECT * FROM procedures ORDER BY name ASC";
            $procedures = Database::fetchAll($sql);
        }

        $formatted = array_map([$this, 'formatProcedure'], $procedures);
        Response::json($formatted);
    }

    public function show(Request $request): void
    {
        $id = (int) $request->param('procedure') ?: (int) $request->param('id');
        $procedure = Database::fetchOne("SELECT * FROM procedures WHERE id = ?", [$id]);

        if (!$procedure) {
            Response::json(['message' => 'Procedimento não encontrado.'], 404);
        }

        Response::json($this->formatProcedure($procedure));
    }

    public function store(Request $request): void
    {
        $professionalId = $request->user['professional']['id'] ?? null;
        if (!$professionalId) {
            Response::json(['message' => 'Apenas profissionais de saúde podem cadastrar procedimentos.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'             => 'required|string|max:255',
            'category'         => 'nullable|string|max:255',
            'description'      => 'nullable|string',
            'base_price'       => 'nullable|numeric',
            'duration_minutes' => 'nullable|integer',
        ]);

        $validated = $validator->validated();

        $id = Database::insert(
            "INSERT INTO procedures (professional_id, name, category, description, base_price, duration_minutes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))",
            [
                $professionalId,
                $validated['name'],
                $validated['category'] ?? null,
                $validated['description'] ?? null,
                $validated['base_price'] ?? 0.0,
                $validated['duration_minutes'] ?? 30,
            ]
        );

        $procedure = Database::fetchOne("SELECT * FROM procedures WHERE id = ?", [$id]);
        Response::json($this->formatProcedure($procedure), 201);
    }

    public function update(Request $request): void
    {
        $id = (int) $request->param('procedure') ?: (int) $request->param('id');
        $professionalId = $request->user['professional']['id'] ?? null;

        $procedure = Database::fetchOne("SELECT * FROM procedures WHERE id = ?", [$id]);
        if (!$procedure) {
            Response::json(['message' => 'Procedimento não encontrado.'], 404);
        }

        if (!$professionalId || $procedure['professional_id'] !== $professionalId) {
            Response::json(['message' => 'Acesso não autorizado para alterar este procedimento.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'             => 'sometimes|required|string|max:255',
            'category'         => 'nullable|string|max:255',
            'description'      => 'nullable|string',
            'base_price'       => 'nullable|numeric',
            'duration_minutes' => 'nullable|integer',
        ]);

        $validated = $validator->validated();

        Database::execute(
            "UPDATE procedures SET name = ?, category = ?, description = ?, base_price = ?, duration_minutes = ?, updated_at = datetime('now') WHERE id = ?",
            [
                $validated['name'] ?? $procedure['name'],
                array_key_exists('category', $validated) ? $validated['category'] : $procedure['category'],
                array_key_exists('description', $validated) ? $validated['description'] : $procedure['description'],
                array_key_exists('base_price', $validated) ? $validated['base_price'] : $procedure['base_price'],
                array_key_exists('duration_minutes', $validated) ? $validated['duration_minutes'] : $procedure['duration_minutes'],
                $id,
            ]
        );

        $updated = Database::fetchOne("SELECT * FROM procedures WHERE id = ?", [$id]);
        Response::json($this->formatProcedure($updated));
    }

    public function destroy(Request $request): void
    {
        $id = (int) $request->param('procedure') ?: (int) $request->param('id');
        $professionalId = $request->user['professional']['id'] ?? null;

        $procedure = Database::fetchOne("SELECT * FROM procedures WHERE id = ?", [$id]);
        if (!$procedure) {
            Response::noContent();
        }

        if (!$professionalId || $procedure['professional_id'] !== $professionalId) {
            Response::json(['message' => 'Acesso não autorizado para remover este procedimento.'], 403);
        }

        Database::execute("DELETE FROM procedures WHERE id = ?", [$id]);
        Response::noContent();
    }

    private function formatProcedure(array $p): array
    {
        return [
            'id'               => (int) $p['id'],
            'professional_id'  => (int) $p['professional_id'],
            'name'             => $p['name'],
            'category'         => $p['category'] ?? null,
            'description'      => $p['description'] ?? null,
            'base_price'       => (float) ($p['base_price'] ?? 0),
            'duration_minutes' => (int) ($p['duration_minutes'] ?? 30),
            'created_at'       => $p['created_at'],
        ];
    }
}
