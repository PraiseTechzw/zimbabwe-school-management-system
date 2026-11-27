<?php

declare(strict_types=1);

namespace App\Domains\AuditTrail\Actions;

use App\Domains\AuditTrail\AuditEventData;
use App\Domains\AuditTrail\AuditScope;
use App\Domains\AuditTrail\Models\AuditEvent;
use App\Domains\AuditTrail\Services\AuditEventRecorder;

/**
 * Stable entry point for domain modules that deliberately record an event.
 */
final readonly class RecordAuditEvent
{
    public function __construct(private AuditEventRecorder $recorder) {}

    public function execute(AuditScope $scope, AuditEventData $data): AuditEvent
    {
        return $this->recorder->record($scope, $data);
    }
}
