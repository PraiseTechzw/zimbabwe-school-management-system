<?php

declare(strict_types=1);

namespace App\Domains\Auth\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Explicit allow-list for authenticated user data.
 *
 * Password hashes, remember tokens and any future sensitive columns can never
 * leak through an auth response because fields are added here deliberately.
 *
 * @mixin User
 */
final class AuthenticatedUserResource extends JsonResource
{
    /** @var string */
    public static $wrap = 'user';

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;

        return [
            'id' => (int) $user->getKey(),
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status->value,
            'email_verified_at' => $user->email_verified_at?->toJSON(),
            'last_login_at' => $user->last_login_at?->toJSON(),
        ];
    }
}
