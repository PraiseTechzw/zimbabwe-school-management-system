<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the academic structure surface without changing shared routing files.
 */
final class AcademicStructureServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware(['web', 'auth', 'school.context'])
            ->prefix('api')
            ->group(base_path('routes/academic-structure.php'));
    }
}
