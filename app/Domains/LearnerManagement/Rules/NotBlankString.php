<?php

declare(strict_types=1);

namespace App\Domains\LearnerManagement\Rules;

use Illuminate\Contracts\Validation\ImplicitRule;
use Illuminate\Contracts\Validation\Rule;

/**
 * Rejects whitespace-only names.
 *
 * Laravel skips ordinary rules when a string trims to an empty value, so this
 * implicit rule is used by both FormRequests and domain actions.
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
