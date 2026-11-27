<?php

declare(strict_types=1);

namespace App\Frontend;

use App\Frontend\Contracts\FrontendDataProvider;

/**
 * Opt-in demo decorator for the dashboard's cross-module panel only.
 *
 * It never decorates user, school, calendar or identifier data: those must
 * always come from the authoritative adapter. Demo values are marked with
 * `is_demo` so the view renders an explicit label instead of implying that a
 * school record exists.
 */
final class DemoFrontendDataProvider implements FrontendDataProvider
{
    public function __construct(private readonly FrontendDataProvider $delegate) {}

    public function user(): ?array
    {
        return $this->delegate->user();
    }

    public function school(): ?array
    {
        return $this->delegate->school();
    }

    public function identifiers(): array
    {
        return $this->delegate->identifiers();
    }

    public function currentYear(): ?array
    {
        return $this->delegate->currentYear();
    }

    public function years(): array
    {
        return $this->delegate->years();
    }

    public function periods(int $academicYearId): array
    {
        return $this->delegate->periods($academicYearId);
    }

    public function attention(): array
    {
        return [
            ['label' => 'Pending admissions', 'value' => '12', 'is_demo' => true],
            ['label' => 'Fees in arrears', 'value' => '8', 'is_demo' => true],
            ['label' => 'Open discipline cases', 'value' => '3', 'is_demo' => true],
        ];
    }
}
