<?php

declare(strict_types=1);

namespace App\Domains\Auth\Actions;

use App\Domains\Auth\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Creates a local user account with server-side validation.
 *
 * There is no public registration route: account provisioning remains an
 * explicit domain operation until an authorised user-management boundary is
 * specified.
 */
final class CreateUser
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): User
    {
        if (isset($attributes['email']) && is_string($attributes['email'])) {
            $attributes['email'] = mb_strtolower(trim($attributes['email']));
        }

        $validated = Validator::make($attributes, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(12)->max(64)->letters()->mixedCase()->numbers()->symbols(),
            ],
            'school_ids' => ['sometimes', 'array', 'list'],
            'school_ids.*' => ['integer', Rule::exists('schools', 'id')],
        ])->validate();

        /** @var array{name: string, email: string, password: string, school_ids?: list<int>} $validated */
        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        // Status is deliberately not mass-assignable; activation is explicit here.
        $user->status = AccountStatus::Active;
        $user->save();

        if (isset($validated['school_ids'])) {
            $user->schools()->sync(array_values(array_unique(array_map('intval', $validated['school_ids']))));
        }

        return $user;
    }
}
