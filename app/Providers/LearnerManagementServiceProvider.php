<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the learner record surface without changing shared routing files.
 */
final class LearnerManagementServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware(['web', 'auth', 'school.context'])
            ->prefix('api')
            ->group(base_path('routes/learners.php'));
    }
}
