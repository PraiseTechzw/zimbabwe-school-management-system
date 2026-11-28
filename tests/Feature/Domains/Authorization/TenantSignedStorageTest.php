<?php

declare(strict_types=1);

use App\Domains\School\Context\SchoolContext;
use App\Domains\School\Models\School;
use App\Http\Middleware\EnsureTenantSignedStorage;
use App\Models\User;
use App\Services\TenantFileStorage;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

beforeEach(fn (): mixed => Storage::fake('local'));

function tenantStorageUser(School ...$schools): User
{
    $user = User::factory()->create();

    foreach ($schools as $school) {
        $user->schools()->attach($school);
        grantSchoolRole($user, $school);
    }

    return $user;
}

function tenantStorageSignedUrl(School $school, string $path, bool $upload = false): string
{
    $context = app(SchoolContext::class);
    $context->set($school);

    try {
        return app(TenantFileStorage::class)->signedUrl($path, now()->addMinutes(10), $upload);
    } finally {
        $context->clear();
    }
}

it('serves a private file through a signed URL bound to the current school', function (): void {
    $school = School::factory()->create();
    $user = tenantStorageUser($school);
    $path = 'schools/'.$school->id.'/notes.txt';

    Storage::disk('local')->put($path, 'School-only note');

    $response = $this->actingAs($user)
        ->withHeader('X-School-Id', (string) $school->id)
        ->get(tenantStorageSignedUrl($school, $path))
        ->assertOk();

    expect($response->streamedContent())->toContain('School-only note');
});

it('accepts an upload only through a signed upload URL', function (): void {
    $school = School::factory()->create();
    $user = tenantStorageUser($school);
    $path = 'schools/'.$school->id.'/uploads/note.txt';

    $this->actingAs($user)
        ->withHeader('X-School-Id', (string) $school->id)
        ->call('PUT', tenantStorageSignedUrl($school, $path, true), [], [], [], [], 'Uploaded note')
        ->assertNoContent();

    expect(Storage::disk('local')->get($path))->toBe('Uploaded note');
});

it('rejects a signed URL replayed in another school context', function (): void {
    $owned = School::factory()->create();
    $other = School::factory()->create();
    $user = tenantStorageUser($owned, $other);
    $path = 'schools/'.$owned->id.'/notes.txt';

    Storage::disk('local')->put($path, 'Owned school note');
    $url = tenantStorageSignedUrl($owned, $path);

    $this->actingAs($user)
        ->withHeader('X-School-Id', (string) $other->id)
        ->get($url)
        ->assertForbidden();
});

it('rejects unsigned URLs and tampered school identifiers', function (): void {
    $school = School::factory()->create();
    $other = School::factory()->create();
    $user = tenantStorageUser($school, $other);
    $path = 'schools/'.$school->id.'/notes.txt';

    Storage::disk('local')->put($path, 'School-only note');

    $this->actingAs($user)
        ->withHeader('X-School-Id', (string) $school->id)
        ->get(route('storage.local', ['path' => $path, TenantFileStorage::TENANT_PARAMETER => $school->id]))
        ->assertForbidden();

    $signed = tenantStorageSignedUrl($school, $path);
    $tampered = str_replace(
        TenantFileStorage::TENANT_PARAMETER.'='.$school->id,
        TenantFileStorage::TENANT_PARAMETER.'='.$other->id,
        $signed,
    );

    expect($tampered)->not->toBe($signed);

    $this->actingAs($user)
        ->withHeader('X-School-Id', (string) $school->id)
        ->get($tampered)
        ->assertForbidden();
});

it('does not allow a download signature to upload or an upload signature to download', function (): void {
    $school = School::factory()->create();
    $user = tenantStorageUser($school);
    $path = 'schools/'.$school->id.'/notes.txt';

    $downloadUrl = tenantStorageSignedUrl($school, $path);
    $uploadUrl = tenantStorageSignedUrl($school, $path, true);

    $this->actingAs($user)
        ->withHeader('X-School-Id', (string) $school->id)
        ->call('PUT', $downloadUrl, [], [], [], [], 'Forbidden upload')
        ->assertForbidden();

    $this->actingAs($user)
        ->withHeader('X-School-Id', (string) $school->id)
        ->get($uploadUrl)
        ->assertForbidden();

    expect(Storage::disk('local')->exists($path))->toBeFalse();
});

it('refuses to sign paths outside the school directory or containing traversal', function (): void {
    $school = School::factory()->create();
    $context = app(SchoolContext::class);
    $context->set($school);

    try {
        $storage = app(TenantFileStorage::class);

        expect(fn (): string => $storage->signedUrl('other/notes.txt', now()->addMinutes(10)))
            ->toThrow(AuthorizationException::class);
        expect(fn (): string => $storage->signedUrl(
            'schools/'.$school->id.'/../'.School::factory()->create()->id.'/notes.txt',
            now()->addMinutes(10),
        ))->toThrow(AuthorizationException::class);
    } finally {
        $context->clear();
    }
});

it('rejects a signed path with dot segments at the storage middleware', function (): void {
    $school = School::factory()->create();
    $user = tenantStorageUser($school);
    $path = 'schools/'.$school->id.'/./notes.txt';

    $context = app(SchoolContext::class);
    $context->set($school);

    try {
        $url = URL::temporarySignedRoute('storage.local', now()->addMinutes(10), [
            'path' => $path,
            TenantFileStorage::TENANT_PARAMETER => $school->id,
        ]);
    } finally {
        $context->clear();
    }

    $this->actingAs($user)
        ->withHeader('X-School-Id', (string) $school->id)
        ->get($url)
        ->assertForbidden();
});

it('keeps private storage routes behind authentication, school context and tenant signature checks', function (): void {
    foreach (['storage.local', 'storage.local.upload'] as $name) {
        $route = app('router')->getRoutes()->getByName($name);

        expect($route)->not->toBeNull()
            ->and($route->gatherMiddleware())->toContain('auth', 'school.context', EnsureTenantSignedStorage::class);
    }
});
