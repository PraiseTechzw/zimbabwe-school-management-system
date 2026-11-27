<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;

it('boots the Laravel application', function (): void {
    expect(app())->toBeInstanceOf(Application::class);
});

it('uses an in-memory SQLite database for tests', function (): void {
    expect(config('database.default'))->toBe('sqlite')
        ->and(config('database.connections.sqlite.database'))->toBe(':memory:');
});

it('serves the framework welcome page', function (): void {
    $this->get('/')->assertOk();
});
