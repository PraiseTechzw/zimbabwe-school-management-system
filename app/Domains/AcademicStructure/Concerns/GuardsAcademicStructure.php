<?php

declare(strict_types=1);

namespace App\Domains\AcademicStructure\Concerns;

use App\Domains\AcademicCalendar\Models\AcademicYear;
use App\Domains\AcademicStructure\Models\ClassDefinition;
use App\Domains\AcademicStructure\Models\EducationalLevel;
use App\Domains\AcademicStructure\Models\GradeLevel;
use App\Domains\School\Models\School;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Shared integrity checks for academic structure writes.
 *
 * Every write locks the school row so concurrent duplicate checks cannot both
 * pass. On SQLite the lock is a no-op because the engine serialises writers.
 * Parent lookups are scoped by the school passed from the server-resolved
 * context; foreign identifiers produce the same generic error as missing ones
 * so they cannot be probed through validation messages.
 */
trait GuardsAcademicStructure
{
    private function lockStructureForSchool(School $school): void
    {
        School::query()->whereKey($school->id)->lockForUpdate()->firstOrFail();
    }

    private function resolveEducationalLevel(School $school, int $id): EducationalLevel
    {
        /** @var EducationalLevel|null $level */
        $level = EducationalLevel::query()->forSchool($school)->whereKey($id)->lockForUpdate()->first();

        if ($level === null || ! $this->belongsToSchool($level, $school)) {
            throw ValidationException::withMessages([
                'educational_level_id' => 'The selected educational level is not available.',
            ]);
        }

        $this->assertActiveParent($level, 'educational_level_id', 'educational level');

        return $level;
    }

    private function resolveGradeLevel(School $school, int $id): GradeLevel
    {
        /** @var GradeLevel|null $grade */
        $grade = GradeLevel::query()->forSchool($school)->whereKey($id)->lockForUpdate()->first();

        if ($grade === null || ! $this->belongsToSchool($grade, $school)) {
            throw ValidationException::withMessages([
                'grade_level_id' => 'The selected grade level is not available.',
            ]);
        }

        $this->assertActiveParent($grade, 'grade_level_id', 'grade level');

        return $grade;
    }

    private function resolveClassDefinition(School $school, int $id, bool $allowInactive = false): ClassDefinition
    {
        /** @var ClassDefinition|null $class */
        $class = ClassDefinition::query()->forSchool($school)->whereKey($id)->lockForUpdate()->first();

        if ($class === null || ! $this->belongsToSchool($class, $school)) {
            throw ValidationException::withMessages([
                'class_definition_id' => 'The selected class definition is not available.',
            ]);
        }

        if (! $allowInactive) {
            $this->assertActiveParent($class, 'class_definition_id', 'class definition');
        }

        return $class;
    }

    private function resolveAcademicYear(School $school, int $id): AcademicYear
    {
        /** @var AcademicYear|null $year */
        $year = AcademicYear::query()->forSchool($school)->whereKey($id)->lockForUpdate()->first();

        if ($year === null || ! $this->belongsToSchool($year, $school)) {
            throw ValidationException::withMessages([
                'academic_year_id' => 'The selected academic year is not available.',
            ]);
        }

        return $year;
    }

    private function assertOwnedBySchool(School $school, Model $model): void
    {
        if (! $this->belongsToSchool($model, $school)) {
            throw new AuthorizationException('The record is outside the authorised school context.');
        }
    }

    private function belongsToSchool(Model $model, School $school): bool
    {
        return (int) $model->getAttribute('school_id') === (int) $school->id;
    }

    private function assertActiveParent(Model $model, string $field, string $label): void
    {
        if (! (bool) config('academic-structure.require_active_parent', true)) {
            return;
        }

        if (! (bool) $model->getAttribute('is_active')) {
            throw ValidationException::withMessages([
                $field => "The selected {$label} is inactive.",
            ]);
        }
    }

    /**
     * @param  class-string<Model>  $model
     * @param  array<string, mixed>  $conditions
     */
    private function assertNoDuplicate(
        string $model,
        array $conditions,
        string $field,
        string $message,
        ?int $ignoreId = null,
    ): void {
        $query = $model::query();

        foreach ($conditions as $column => $value) {
            $query->where($column, $value);
        }

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->lockForUpdate()->exists()) {
            throw ValidationException::withMessages([$field => $message]);
        }
    }

    /**
     * Case-insensitive uniqueness for names and codes across database drivers.
     *
     * @param  class-string<Model>  $model
     * @param  array<string, mixed>  $conditions
     */
    private function assertUniqueLabel(
        string $model,
        string $column,
        string $value,
        array $conditions,
        string $field,
        string $message,
        ?int $ignoreId = null,
    ): void {
        $query = $model::query()
            ->whereRaw('LOWER('.$column.') = ?', [Str::lower($value)]);

        foreach ($conditions as $key => $condition) {
            $query->where($key, $condition);
        }

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->lockForUpdate()->exists()) {
            throw ValidationException::withMessages([$field => $message]);
        }
    }
}
