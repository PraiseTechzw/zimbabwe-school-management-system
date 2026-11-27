<?php

declare(strict_types=1);

namespace App\Domains\AuditTrail\Models;

use App\Domains\School\Models\School;
use Carbon\CarbonInterface;
use Database\Factories\AuditEventFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Append-only audit record.
 *
 * Writes are made only through the audit domain recorder. Eloquent instances
 * and the model query builder both refuse updates and deletes.
 *
 * @property int $id
 * @property int|null $school_id
 * @property string|null $actor_type
 * @property string|null $actor_id
 * @property string $action
 * @property string|null $category
 * @property string $resource_type
 * @property string $resource_id
 * @property CarbonInterface $occurred_at
 * @property string|null $correlation_id
 * @property string|null $source
 * @property array<string, mixed>|null $metadata
 * @property array<string, mixed>|null $changes
 * @property CarbonInterface $created_at
 *
 * @method static Builder<AuditEvent> forSchool(School|int $school)
 * @method static Builder<AuditEvent> system()
 */
#[Guarded(['*'])]
class AuditEvent extends Model
{
    /** @use HasFactory<AuditEventFactory> */
    use HasFactory;

    protected $table = 'audit_events';

    public $timestamps = false;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'occurred_at' => 'immutable_datetime',
        'created_at' => 'immutable_datetime',
        'metadata' => 'array',
        'changes' => 'array',
    ];

    protected static function newFactory(): Factory
    {
        return AuditEventFactory::new();
    }

    protected static function booted(): void
    {
        static::updating(function (AuditEvent $event): void {
            throw new LogicException('Audit events are append-only and cannot be updated.');
        });

        static::deleting(function (AuditEvent $event): void {
            throw new LogicException('Audit events are append-only and cannot be deleted.');
        });
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     */
    public function newEloquentBuilder($query): ImmutableAuditEventBuilder
    {
        return new ImmutableAuditEventBuilder($query);
    }

    /**
     * @param  Builder<AuditEvent>  $query
     */
    public function scopeForSchool(Builder $query, School|int $school): void
    {
        $query->where('school_id', $school instanceof School ? (int) $school->id : $school);
    }

    /**
     * @param  Builder<AuditEvent>  $query
     */
    public function scopeSystem(Builder $query): void
    {
        $query->whereNull('school_id');
    }
}
