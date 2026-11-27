<?php

declare(strict_types=1);

namespace App\Domains\AcademicStructure\Rules;

use Illuminate\Contracts\Validation\ImplicitRule;
use Illuminate\Contracts\Validation\Rule;

/**
 * Rejects whitespace-only strings.
 *
 * Laravel skips ordinary rules when a string trims to an empty value, so a
 * plain not_regex rule cannot guard names and codes. Implicit rules are always
 * evaluated.
 */
final class NotBlankString implements ImplicitRule, Rule
{
    /**
     * @param  string  $attribute
     * @param  mixed  $value
     */
    public function passes($attribute, $value): bool
    {
        return $value === null || (is_string($value) && trim($value) !== '');
    }

    public function message(): string
    {
        return 'The :attribute field must not be blank.';
    }
}
