<?php

declare(strict_types=1);

namespace App\Domains\Authorization\Console;

use App\Domains\Authorization\Models\RoleAssignment;
use App\Domains\School\Models\School;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

/**
 * Trusted bootstrap path for the first role assignment in a school.
 *
 * This command is intentionally CLI-only; HTTP callers must use the
 * permission-protected domain actions.
 */
final class AssignRoleCommand extends Command
{
    protected $signature = 'roles:assign
                            {--school= : Authorised school identifier}
                            {--user= : User identifier}
                            {--role= : Configured role slug}';

    protected $description = 'Assign a configured role to a user in one school.';

    public function handle(): int
    {
        $schoolValue = (string) $this->option('school');
        $userValue = (string) $this->option('user');
        $role = (string) $this->option('role');

        if (! ctype_digit($schoolValue) || ! ctype_digit($userValue)) {
            $this->error('School and user identifiers must be integers.');

            return SymfonyCommand::FAILURE;
        }

        /** @var array<string, mixed> $roles */
        $roles = (array) config('authorization.roles', []);

        if (! array_key_exists($role, $roles)) {
            $this->error('The selected role is not configured.');

            return SymfonyCommand::FAILURE;
        }

        $school = School::query()->findOrFail((int) $schoolValue);
        $user = User::query()->findOrFail((int) $userValue);

        if (! $user->schools()->whereKey($school->id)->exists()) {
            $this->error('The user is not a member of the selected school.');

            return SymfonyCommand::FAILURE;
        }

        if (RoleAssignment::query()->forSchool($school)->forUser($user)->where('role', $role)->exists()) {
            $this->error('That role is already assigned in this school.');

            return SymfonyCommand::FAILURE;
        }

        RoleAssignment::query()->forceCreate([
            'school_id' => (int) $school->id,
            'user_id' => (int) $user->id,
            'role' => $role,
            'source' => 'cli',
            'created_at' => Carbon::now(),
        ]);

        $this->info(sprintf('Assigned %s to user %d in school %d.', $role, (int) $user->id, (int) $school->id));

        return SymfonyCommand::SUCCESS;
    }
}
