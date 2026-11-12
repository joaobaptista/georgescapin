<?php

declare(strict_types=1);

namespace App\Application\Catalog;

use App\Application\Shared\Actor;
use App\Domain\Catalog\Procedure;
use App\Domain\Catalog\ProcedureRepository;
use App\Domain\Shared\Clock;
use App\Domain\Shared\NotFound;

/** Each professional manages only their own procedures. */
final class ManageProcedures
{
    public function __construct(
        private readonly ProcedureRepository $procedures,
        private readonly Clock $clock,
    ) {
    }

    /** Other professionals' procedures are reported as missing, not forbidden. */
    public function assertVisible(Actor $actor, int $id): void
    {
        if (!$this->procedures->find($id)?->belongsTo($actor->professionalId)) {
            throw new NotFound('Procedimento não encontrado.');
        }
    }

    /** @param array{name: string, category?: ?string, description?: ?string, base_price?: mixed, duration_minutes?: mixed} $input */
    public function create(Actor $actor, array $input): int
    {
        $professionalId = $actor->professionalIdOrFail('Apenas profissionais de saúde podem cadastrar procedimentos.');

        return $this->procedures->save(new Procedure(
            null,
            $professionalId,
            $input['name'],
            $input['category'] ?? null,
            $input['description'] ?? null,
            (float) ($input['base_price'] ?? 0.0),
            (int) ($input['duration_minutes'] ?? 30),
        ), $this->clock->now());
    }

    /** Fields absent from $input keep their current value. */
    public function update(Actor $actor, int $id, array $input): void
    {
        $procedure = $this->procedures->find($id) ?? throw new NotFound('Procedimento não encontrado.');
        $procedure->assertOwnedBy($actor->professionalId, 'alterar');

        $procedure->name = $input['name'] ?? $procedure->name;
        foreach (['category' => 'category', 'description' => 'description'] as $field => $property) {
            if (array_key_exists($field, $input)) {
                $procedure->{$property} = $input[$field];
            }
        }
        if (array_key_exists('base_price', $input)) {
            $procedure->basePrice = (float) ($input['base_price'] ?? 0);
        }
        if (array_key_exists('duration_minutes', $input)) {
            $procedure->durationMinutes = (int) ($input['duration_minutes'] ?? 30);
        }

        $this->procedures->save($procedure, $this->clock->now());
    }

    /** Idempotent: removing a missing procedure succeeds. */
    public function remove(Actor $actor, int $id): void
    {
        $procedure = $this->procedures->find($id);
        if ($procedure === null) {
            return;
        }
        $procedure->assertOwnedBy($actor->professionalId, 'remover');
        $this->procedures->remove($id);
    }
}
