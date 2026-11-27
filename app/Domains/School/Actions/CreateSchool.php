<?php

declare(strict_types=1);

namespace App\Domains\School\Actions;

use App\Domains\School\Models\School;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class CreateSchool
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): School
    {
        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', Rule::in(array_keys((array) config('schools.school_types', [])))],
            'ward' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
        ])->validate();

        return School::query()->create([
            'name' => trim((string) $validated['name']),
            'type' => isset($validated['type']) ? (string) $validated['type'] : null,
            'ward' => $this->nullableString($validated, 'ward'),
            'district' => $this->nullableString($validated, 'district'),
            'province' => $this->nullableString($validated, 'province'),
        ]);
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
