<?php

declare(strict_types=1);

namespace App\Domains\SchoolSettings\Actions;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Domain-level validation shared by HTTP requests and internal callers.
 */
final class ValidateSchoolSettings
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        /** @var array<int, mixed> $locales */
        $locales = array_values((array) config('school-settings.locales', ['en']));

        return [
            'school_id' => ['missing'],
            'password' => ['prohibited'],
            'secret' => ['prohibited'],
            'token' => ['prohibited'],
            'api_key' => ['prohibited'],
            'private_key' => ['prohibited'],
            'credentials' => ['prohibited'],
            'locale' => ['sometimes', 'required', 'string', Rule::in($locales)],
            'timezone' => ['sometimes', 'required', 'string', 'timezone'],
            'source' => ['required', 'string', 'max:255', 'not_regex:/^\s*$/'],
            'effective_from' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function execute(array $data): array
    {
        /** @var array<string, mixed> $validated */
        $validated = Validator::make($data, self::rules())->validate();

        return $validated;
    }
}
