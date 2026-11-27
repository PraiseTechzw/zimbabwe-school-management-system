<?php

declare(strict_types=1);

namespace App\Domains\AuditTrail;

use App\Domains\School\Models\School;

/**
 * Server-resolved scope for an audit write.
 *
 * A school scope can only be built from an already authorised School model.
 * There is deliberately no factory that accepts a raw client-supplied ID.
 */
final readonly class AuditScope
{
    private function __construct(
        public ?School $school,
        public bool $system,
    ) {}

    public static function forSchool(School $school): self
    {
        return new self($school, false);
    }

    public static function system(): self
    {
        return new self(null, true);
    }

    public function schoolId(): ?int
    {
        return $this->school === null ? null : (int) $this->school->id;
    }
}
