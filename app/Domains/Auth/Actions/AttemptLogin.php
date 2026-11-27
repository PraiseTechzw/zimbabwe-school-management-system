<?php

declare(strict_types=1);

namespace App\Domains\Auth\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Verifies credentials and maintains the local account lockout state.
 *
 * Exactly one hash verification runs on every path so response timing cannot
 * distinguish an unknown email from a known account. Session handling stays in
 * the HTTP layer.
 */
final class AttemptLogin
{
    private const MAX_FAILED_ATTEMPTS = 5;

    private const LOCK_MINUTES = 15;

    private static ?string $dummyHash = null;

    public function handle(string $email, string $password): ?User
    {
        $user = User::query()->where('email', mb_strtolower(trim($email)))->first();
        $passwordMatches = Hash::check($password, $this->hashFor($user));

        if ($user === null) {
            return null;
        }

        if ($user->locked_until !== null && $user->locked_until->isPast()) {
            $this->update($user, ['locked_until' => null]);
        }

        if (! $passwordMatches) {
            $this->registerFailure($user);

            return null;
        }

        if (! $user->canAuthenticate() || $user->isLocked()) {
            return null;
        }

        $this->update($user, [
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
        ]);

        return $user;
    }

    private function hashFor(?User $user): string
    {
        if ($user !== null) {
            return (string) $user->getAuthPassword();
        }

        return self::$dummyHash ??= Hash::make(Str::random(32));
    }

    /**
     * The counter is read and written under a row lock so concurrent guesses
     * cannot lose increments and bypass the lockout threshold.
     */
    private function registerFailure(User $user): void
    {
        DB::transaction(function () use ($user): void {
            /** @var User $fresh */
            $fresh = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            $attempts = ((int) $fresh->failed_login_attempts) + 1;
            $values = ['failed_login_attempts' => $attempts];

            if ($attempts >= self::MAX_FAILED_ATTEMPTS) {
                $values['failed_login_attempts'] = 0;
                $values['locked_until'] = now()->addMinutes(self::LOCK_MINUTES);
            }

            $this->update($user, $values);
        });
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function update(User $user, array $values): void
    {
        User::query()->whereKey($user->getKey())->update($values);
        $user->forceFill($values);
    }
}
