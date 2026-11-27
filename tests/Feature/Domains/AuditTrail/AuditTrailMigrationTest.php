<?php

declare(strict_types=1);

use App\Domains\AuditTrail\Models\AuditEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates an append-oriented audit table with tenant and classification columns', function (): void {
    expect(Schema::hasTable('audit_events'))->toBeTrue();

    expect(Schema::hasColumns('audit_events', [
        'id',
        'school_id',
        'actor_type',
        'actor_id',
        'action',
        'category',
        'resource_type',
        'resource_id',
        'occurred_at',
        'correlation_id',
        'source',
        'metadata',
        'changes',
        'created_at',
    ]))->toBeTrue();

    expect(Schema::hasColumn('audit_events', 'updated_at'))->toBeFalse();
});

it('protects audit rows from school deletion through the tenant foreign key', function (): void {
    /** @var array<int, object> $foreignKeys */
    $foreignKeys = DB::select('PRAGMA foreign_key_list(audit_events)');

    $schoolKey = collect($foreignKeys)->first(fn (object $key): bool => $key->table === 'schools');

    expect($schoolKey)->not()->toBeNull()
        ->and($schoolKey->on_delete)->toBe('RESTRICT');
});

it('stores metadata and changes as structured json without sensitive columns', function (): void {
    $event = AuditEvent::factory()->create([
        'school_id' => null,
        'metadata' => ['safe' => 'value'],
        'changes' => ['before' => null, 'after' => ['status' => 'published']],
    ]);

    $fresh = AuditEvent::query()->whereKey($event->id)->firstOrFail();

    expect($fresh->metadata)->toBe(['safe' => 'value'])
        ->and($fresh->changes)->toBe(['before' => null, 'after' => ['status' => 'published']])
        ->and(fn () => $fresh->delete())->toThrow(LogicException::class);
});
