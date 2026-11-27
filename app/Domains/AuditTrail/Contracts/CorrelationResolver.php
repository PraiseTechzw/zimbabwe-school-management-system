<?php

declare(strict_types=1);

namespace App\Domains\AuditTrail\Contracts;

interface CorrelationResolver
{
    public function resolve(): ?string;
}
