<?php

declare(strict_types=1);

namespace App\Domains\LearnerManagement\Concerns;

use App\Domains\AcademicStructure\Models\ClassOffering;
use App\Domains\School\Models\School;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * Integrity checks shared by learner writes.
 *
 * Writes lock the server-resolved school row so placement checks cannot race.
 * Parent lookups are school-scoped, and missing or foreign offerings produce
 * the same generic message so another tenant cannot be probed.
 */
trait GuardsLearnerRecords
{
    private function lockLearnerSchool(School $school): void
    {
        School::query()->whereKey($school->id)->lockForUpdate()->firstOrFail();
    }

    private function resolvePlacement(School $school, int $id): ClassOffering
    {
        /** @var ClassOffering|null $offering */
        $offering = ClassOffering::query()
            ->forSchool($school)
            ->whereKey($id)
            ->lockForUpdate()
            ->first();

        if ($offering === null || ! $this->belongsToSchool($offering, $school)) {
            throw ValidationException::withMessages([
                'class_offering_id' => 'The selected class offering is not available.',
            ]);
        }

        if (
            (bool) config('learner-management.require_active_placement', true)
            && ! (bool) $offering->is_active
        ) {
            throw ValidationException::withMessages([
                'class_offering_id' => 'The selected class offering is inactive.',
            ]);
        }

        return $offering;
    }

    private function assertOwnedBySchool(School $school, Model $learner): void
    {
        if (! $this->belongsToSchool($learner, $school)) {
            throw new AuthorizationException('The record is outside the authorised school context.');
        }
    }

    private function belongsToSchool(Model $model, School $school): bool
    {
        return (int) $model->getAttribute('school_id') === (int) $school->id;
    }
}
