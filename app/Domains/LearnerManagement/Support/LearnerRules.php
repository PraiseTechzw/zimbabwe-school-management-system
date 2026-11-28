<?php

declare(strict_types=1);

namespace App\Domains\LearnerManagement\Support;

use App\Domains\LearnerManagement\Rules\NotBlankString;

/**
 * Shared validation rules for HTTP requests and domain actions.
 *
 * The rules are intentionally generic: no statutory field list, national
 * identifier, age band or school naming convention is encoded here.
 */
final class LearnerRules
{
    /**
     * @return array<int, mixed>
     */
    public static function fullName(): array
    {
        return ['required', 'string', 'max:255', new NotBlankString];
    }

    /**
     * @return array<int, mixed>
     */
    public static function dateOfBirth(): array
    {
        return ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'];
    }

    /**
     * @return array<int, mixed>
     */
    public static function classOffering(): array
    {
        return ['nullable', 'integer', 'min:1'];
    }

    /**
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
