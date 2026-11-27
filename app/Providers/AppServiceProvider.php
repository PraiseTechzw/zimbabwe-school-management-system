<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domains\School\Context\SchoolContext;
use App\Frontend\Contracts\FrontendDataProvider;
use App\Frontend\DemoFrontendDataProvider;
use App\Frontend\ServerFrontendDataProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::component('layouts.guest', 'guest-layout');
    }
}
