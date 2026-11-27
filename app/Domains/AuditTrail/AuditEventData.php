<?php

declare(strict_types=1);

namespace App\Domains\AuditTrail;

use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Explicit, deliberate audit input from a domain module.
 */
final readonly class AuditEventData
{
    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>  $changes
     */
    public function __construct(
        public string $action,
        public string $resourceType,
        public string $resourceId,
        public ?string $category = null,
        public array $metadata = [],
        public array $changes = [],
        public ?AuditActor $actor = null,
        public ?string $correlationId = null,
        public ?string $source = null,
        public ?CarbonInterface $occurredAt = null,
    ) {
        $this->assertSlug($action, 'action', 150);
        $this->assertSlug($resourceType, 'resource type', 150);

        if ($resourceId === '' || strlen($resourceId) > 255) {
            throw new InvalidArgumentException('An audit resource identifier must be 1-255 characters.');
        }

        if ($category !== null) {
            $this->assertSlug($category, 'category', 100);
        }

        if ($source !== null) {
            $this->assertSlug($source, 'source', 50);
        }
    }

    private function assertSlug(string $value, string $label, int $maximum): void
    {
        if (preg_match('/^[a-z0-9][a-z0-9._:-]*$/', $value) !== 1 || strlen($value) > $maximum) {
            throw new InvalidArgumentException(sprintf('An audit %s must be a lowercase slug of at most %d characters.', $label, $maximum));
        }
    }
}
