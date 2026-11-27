<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domains\Authorization\Console\AssignRoleCommand;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the authorization surface without changing shared routing files.
 */
final class RolesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands([AssignRoleCommand::class]);
    }

    public function boot(): void
    {
        Route::middleware(['web', 'auth', 'school.context'])
            ->prefix('api')
            ->group(base_path('routes/roles.php'));
    }
}
