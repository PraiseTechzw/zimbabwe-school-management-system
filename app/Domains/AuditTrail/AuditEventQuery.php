<?php

declare(strict_types=1);

namespace App\Domains\AuditTrail;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Read-only criteria for the trusted, server-side audit query action.
 */
final readonly class AuditEventQuery
{
    public function __construct(
        public ?string $action = null,
        public ?string $category = null,
        public ?string $resourceType = null,
        public ?string $resourceId = null,
        public ?string $actorId = null,
        public ?string $from = null,
        public ?string $to = null,
        public int $perPage = 25,
        public int $page = 1,
    ) {
        foreach (['action' => $action, 'category' => $category, 'resourceType' => $resourceType] as $label => $value) {
            if ($value !== null && preg_match('/^[a-z0-9][a-z0-9._:-]*$/', $value) !== 1) {
                throw new InvalidArgumentException(sprintf('The audit %s filter must be a lowercase slug.', $label));
            }
        }

        if ($resourceId !== null && ($resourceId === '' || strlen($resourceId) > 255)) {
            throw new InvalidArgumentException('The audit resource identifier filter must be 1-255 characters.');
        }

        if ($actorId !== null && ($actorId === '' || strlen($actorId) > 255)) {
            throw new InvalidArgumentException('The audit actor identifier filter must be 1-255 characters.');
        }

        if ($from !== null) {
            $this->assertDate($from, 'from');
        }

        if ($to !== null) {
            $this->assertDate($to, 'to');
        }

        if ($perPage < 1 || $perPage > 100 || $page < 1) {
            throw new InvalidArgumentException('Audit pagination requires a page of at least 1 and a per-page value of 1-100.');
        }
    }

    private function assertDate(string $value, string $label): void
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        if ($parsed === false || $parsed->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException(sprintf('The audit %s filter must use Y-m-d.', $label));
        }
    }
}
