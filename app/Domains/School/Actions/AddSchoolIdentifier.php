<?php

declare(strict_types=1);

namespace App\Domains\School\Actions;

use App\Domains\School\Models\School;
use App\Domains\School\Models\SchoolIdentifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class AddSchoolIdentifier
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(School $school, array $data): SchoolIdentifier
    {
        $validated = Validator::make($data, [
            'type' => ['required', Rule::in(array_keys((array) config('schools.identifier_types', [])))],
            'value' => ['required', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:255'],
            'verification_status' => ['nullable', Rule::in(array_keys((array) config('schools.verification_statuses', [])))],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ])->validate();

        $type = (string) $validated['type'];
        $value = trim((string) $validated['value']);
        $normalizedValue = mb_strtolower($value);

        $this->assertConfiguredPatternMatches($type, $value);

        return DB::transaction(function () use ($school, $validated, $type, $value, $normalizedValue): SchoolIdentifier {
            $this->assertValueIsUnique($type, $normalizedValue);

            return SchoolIdentifier::query()->create([
                'school_id' => $school->id,
                'type' => $type,
                'value' => $value,
                'normalized_value' => $normalizedValue,
                'source' => $this->nullableString($validated, 'source'),
                'verification_status' => isset($validated['verification_status']) ? (string) $validated['verification_status'] : null,
                'valid_from' => isset($validated['valid_from']) ? (string) $validated['valid_from'] : null,
                'valid_to' => isset($validated['valid_to']) ? (string) $validated['valid_to'] : null,
            ]);
        });
    }

    private function assertConfiguredPatternMatches(string $type, string $value): void
    {
        $identifierTypes = (array) config('schools.identifier_types', []);
        $configuration = $identifierTypes[$type] ?? null;
        $pattern = is_array($configuration) ? ($configuration['pattern'] ?? null) : null;

        if (! is_string($pattern) || $pattern === '') {
            return;
        }

        if (preg_match($pattern, $value) !== 1) {
            throw ValidationException::withMessages([
                'value' => 'The identifier value does not match the configured pattern.',
            ]);
        }
    }

    private function assertValueIsUnique(string $type, string $normalizedValue): void
    {
        $exists = SchoolIdentifier::query()
            ->where('type', $type)
            ->where('normalized_value', $normalizedValue)
            ->lockForUpdate()
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'value' => 'This identifier value is already recorded.',
            ]);
        }
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
