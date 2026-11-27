<?php

declare(strict_types=1);

namespace App\Domains\AuditTrail\Services;

use App\Domains\AuditTrail\AuditActor;
use App\Domains\AuditTrail\AuditEventData;
use App\Domains\AuditTrail\AuditScope;
use App\Domains\AuditTrail\Contracts\ActorResolver;
use App\Domains\AuditTrail\Contracts\CorrelationResolver;
use App\Domains\AuditTrail\Models\AuditEvent;
use App\Domains\AuditTrail\Support\AuthenticatedActorResolver;
use App\Domains\AuditTrail\Support\RequestCorrelationResolver;
use App\Domains\AuditTrail\Support\SanitizeAuditData;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * The single supported way to append an audit event.
 */
final class AuditEventRecorder
{
    public function __construct(
        private readonly ?ActorResolver $actorResolver = null,
        private readonly ?CorrelationResolver $correlationResolver = null,
    ) {}

    public function record(AuditScope $scope, AuditEventData $data): AuditEvent
    {
        $actor = $data->actor ?? $this->resolveActor();
        $this->assertActor($actor);

        $source = $data->source ?? 'domain';
        $this->assertSource($source);

        $correlationId = $data->correlationId ?? $this->resolveCorrelationId();

        if ($correlationId !== null) {
            $this->assertCorrelationId($correlationId);
        }

        $metadata = SanitizeAuditData::sanitize($data->metadata, 'metadata');
        $changes = SanitizeAuditData::sanitize($data->changes, 'changes');
        $now = Carbon::now();
        $occurredAt = $data->occurredAt ?? $now;

        $event = new AuditEvent;
        $event->forceFill([
            'school_id' => $scope->schoolId(),
            'actor_type' => $actor->type,
            'actor_id' => $actor->id,
            'action' => $data->action,
            'category' => $data->category,
            'resource_type' => $data->resourceType,
            'resource_id' => $data->resourceId,
            'occurred_at' => $occurredAt,
            'correlation_id' => $correlationId,
            'source' => $source,
            'metadata' => $metadata === [] ? null : $metadata,
            'changes' => $changes === [] ? null : $changes,
            'created_at' => $now,
        ]);
        $event->save();

        return $event;
    }

    private function resolveActor(): AuditActor
    {
        $resolver = $this->actorResolver ?? new AuthenticatedActorResolver;

        return $resolver->resolve();
    }

    private function resolveCorrelationId(): ?string
    {
        $resolver = $this->correlationResolver ?? new RequestCorrelationResolver;

        return $resolver->resolve();
    }

    private function assertActor(AuditActor $actor): void
    {
        /** @var array<string, mixed> $types */
        $types = (array) config('audit.actor_types', []);

        if (! array_key_exists($actor->type, $types)) {
            throw new InvalidArgumentException('The audit actor type is not allow-listed.');
        }

        if ($actor->id === '' || strlen($actor->id) > 255) {
            throw new InvalidArgumentException('An audit actor identifier must be 1-255 characters.');
        }
    }

    private function assertSource(string $source): void
    {
        /** @var array<string, mixed> $sources */
        $sources = (array) config('audit.sources', []);

        if (! array_key_exists($source, $sources)) {
            throw new InvalidArgumentException('The audit source is not allow-listed.');
        }
    }

    private function assertCorrelationId(string $correlationId): void
    {
        $maximum = (int) config('audit.limits.correlation_length', 100);

        if (strlen($correlationId) > $maximum || preg_match('/^[A-Za-z0-9._:-]+$/', $correlationId) !== 1) {
            throw new InvalidArgumentException('The audit correlation identifier contains unsupported characters or is too long.');
        }
    }
}
