<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domains\School\Context\SchoolContext;
use App\Frontend\Contracts\AcademicStructureGateway;
use App\Frontend\Contracts\CalendarManagementGateway;
use App\Frontend\Contracts\FrontendDataProvider;
use App\Frontend\Contracts\RoleAssignmentGateway;
use App\Frontend\Contracts\SchoolSettingsGateway;
use App\Frontend\DemoFrontendDataProvider;
use App\Frontend\DomainAcademicStructureGateway;
use App\Frontend\DomainCalendarManagementGateway;
use App\Frontend\DomainRoleAssignmentGateway;
use App\Frontend\DomainSchoolSettingsGateway;
use App\Frontend\ServerFrontendDataProvider;
use App\Http\Middleware\ResolveSchoolContext;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SchoolContext::class);

        $this->app->bind(FrontendDataProvider::class, static function (Application $app): FrontendDataProvider {
            $provider = $app->make(ServerFrontendDataProvider::class);

            return (bool) config('frontend.demo.enabled')
                ? new DemoFrontendDataProvider($provider)
                : $provider;
        });

        $this->app->bind(CalendarManagementGateway::class, DomainCalendarManagementGateway::class);
        $this->app->bind(AcademicStructureGateway::class, DomainAcademicStructureGateway::class);
        $this->app->bind(SchoolSettingsGateway::class, DomainSchoolSettingsGateway::class);
        $this->app->bind(RoleAssignmentGateway::class, DomainRoleAssignmentGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::component('layouts.guest', 'guest-layout');

        // Livewire updates bypass the original page route. Re-run the school
        // context resolver so writes remain tenant-scoped on every update.
        Livewire::addPersistentMiddleware(ResolveSchoolContext::class);
    }
}
