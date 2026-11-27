<?php

declare(strict_types=1);

namespace App\Domains\School\Actions;

use App\Domains\School\Models\School;
use App\Domains\School\Models\SchoolRelationship;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Internal domain action. It assumes the caller has already resolved school
 * context and authorization; do not expose it over HTTP until the tenancy and
 * permission boundary is implemented.
 */
final class RecordSchoolRelationship
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(School $school, array $data): SchoolRelationship
    {
        $validated = Validator::make($data, [
            'related_school_id' => ['required', 'integer', Rule::exists('schools', 'id')],
            'relationship_type' => ['required', Rule::in(array_keys((array) config('schools.relationship_types', [])))],
            'valid_from' => ['required', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'evidence_reference' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $schoolId = (int) $school->id;
        $relatedSchoolId = (int) $validated['related_school_id'];
        $relationshipType = (string) $validated['relationship_type'];
        $validFrom = (string) $validated['valid_from'];

        if ($schoolId === $relatedSchoolId) {
            throw ValidationException::withMessages([
                'related_school_id' => 'A school cannot be related to itself.',
            ]);
        }

        return DB::transaction(function () use ($schoolId, $relatedSchoolId, $relationshipType, $validFrom, $validated): SchoolRelationship {
            if ($this->duplicateExists($schoolId, $relatedSchoolId, $relationshipType, $validFrom)) {
                throw ValidationException::withMessages([
                    'valid_from' => 'This relationship is already recorded from that date.',
                ]);
            }

            if ($this->wouldCreateCycle($schoolId, $relatedSchoolId)) {
                throw ValidationException::withMessages([
                    'related_school_id' => 'This relationship would create a cycle.',
                ]);
            }

            return SchoolRelationship::query()->create([
                'school_id' => $schoolId,
                'related_school_id' => $relatedSchoolId,
                'relationship_type' => $relationshipType,
                'valid_from' => $validFrom,
                'valid_to' => isset($validated['valid_to']) ? (string) $validated['valid_to'] : null,
                'evidence_reference' => $this->nullableString($validated, 'evidence_reference'),
            ]);
        });
    }

    private function duplicateExists(
        int $schoolId,
        int $relatedSchoolId,
        string $relationshipType,
        string $validFrom,
    ): bool {
        return SchoolRelationship::query()
            ->where('school_id', $schoolId)
            ->where('related_school_id', $relatedSchoolId)
            ->where('relationship_type', $relationshipType)
            ->whereDate('valid_from', $validFrom)
            ->lockForUpdate()
            ->exists();
    }

    private function wouldCreateCycle(int $schoolId, int $relatedSchoolId): bool
    {
        $visited = [];
        $queue = [$relatedSchoolId];

        while ($queue !== []) {
            $current = (int) array_shift($queue);

            if ($current === $schoolId) {
                return true;
            }

            if (in_array($current, $visited, true)) {
                continue;
            }

            $visited[] = $current;

            /** @var array<int> $next */
            $next = SchoolRelationship::query()
                ->where('school_id', $current)
                ->lockForUpdate()
                ->pluck('related_school_id')
                ->all();

            foreach ($next as $nextSchoolId) {
                $queue[] = (int) $nextSchoolId;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function nullableString(array $validated, string $key): ?string
    {
        $value = $validated[$key] ?? null;

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
