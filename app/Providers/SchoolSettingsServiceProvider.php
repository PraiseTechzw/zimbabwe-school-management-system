<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the school settings surface without editing shared routing files.
 */
final class SchoolSettingsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware(['web', 'auth', 'school.context'])
            ->prefix('api')
            ->group(base_path('routes/school-settings.php'));
    }
}
