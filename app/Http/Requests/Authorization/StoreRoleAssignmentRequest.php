<?php

declare(strict_types=1);

namespace App\Http\Requests\Authorization;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreRoleAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'school_id' => ['missing'],
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'role' => ['required', Rule::in(array_keys((array) config('authorization.roles', [])))],
        ];
    }
}
