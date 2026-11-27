<?php

declare(strict_types=1);

namespace App\Domains\School\Actions;

use App\Domains\School\Models\School;
use App\Domains\School\Models\SchoolStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class RecordSchoolStatus
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(School $school, array $data): SchoolStatus
    {
        $validated = Validator::make($data, [
            'status' => ['required', Rule::in(array_keys((array) config('schools.statuses', [])))],
            'valid_from' => ['required', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'source' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $status = (string) $validated['status'];
        $validFrom = (string) $validated['valid_from'];
        $validTo = isset($validated['valid_to']) ? (string) $validated['valid_to'] : null;

        return DB::transaction(function () use ($school, $validated, $status, $validFrom, $validTo): SchoolStatus {
            if ($this->overlaps((int) $school->id, $validFrom, $validTo)) {
                throw ValidationException::withMessages([
                    'valid_from' => 'An overlapping status period is already recorded for this school.',
                ]);
            }

            return SchoolStatus::query()->create([
                'school_id' => $school->id,
                'status' => $status,
                'valid_from' => $validFrom,
                'valid_to' => $validTo,
                'source' => $this->nullableString($validated, 'source'),
            ]);
        });
    }

    private function overlaps(int $schoolId, string $validFrom, ?string $validTo): bool
    {
        return SchoolStatus::query()
            ->where('school_id', $schoolId)
            ->when($validTo !== null, function (Builder $query) use ($validTo): void {
                $query->where('valid_from', '<=', $validTo);
            })
            ->where(function (Builder $query) use ($validFrom): void {
                $query->whereNull('valid_to')
                    ->orWhere('valid_to', '>=', $validFrom);
            })
            ->lockForUpdate()
            ->exists();
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
