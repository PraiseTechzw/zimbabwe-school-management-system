<?php

declare(strict_types=1);

namespace App\Domains\AcademicStructure\Support;

use App\Domains\AcademicStructure\Rules\NotBlankString;

/**
 * Shared validation rules for actions and HTTP requests.
 *
 * Rules are deliberately generic: no level, grade or class name is prescribed.
 */
final class AcademicStructureRules
{
    /**
     * @return array<int, mixed>
     */
    public static function name(): array
    {
        return ['required', 'string', 'max:255', new NotBlankString];
    }

    /**
     * @return array<int, mixed>
     */
    public static function code(): array
    {
        return ['nullable', 'string', 'max:64', new NotBlankString];
    }

    /**
     * @return array<int, mixed>
     */
    public static function displayOrder(): array
    {
        return [
            'required',
            'integer',
            'min:1',
            'max:'.(int) config('academic-structure.max_display_order', 10000),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public static function active(): array
    {
        return ['boolean'];
    }

    /**
     * @return array<int, mixed>
     */
    public static function configuration(): array
    {
        return ['nullable', 'array'];
    }

    /**
     * Turns a required rule set into a partial-update rule set.
     *
     * @param  array<int, mixed>  $rules
     * @return array<int, mixed>
     */
    public static function optional(array $rules): array
    {
        $rules = array_values(array_filter(
            $rules,
            fn (mixed $rule): bool => $rule !== 'required',
        ));

        return array_merge(['sometimes'], $rules);
    }
}
