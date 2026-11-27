<?php

declare(strict_types=1);

namespace App\Domains\Auth\Enums;

/**
 * Lifecycle state of a local user account.
 *
 * Only active accounts may authenticate. The values are deliberately narrow:
 * no Product Owner-confirmed lifecycle vocabulary exists yet, so this is an
 * engineering baseline rather than an asserted requirement.
 */
enum AccountStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Disabled = 'disabled';

    public function canAuthenticate(): bool
    {
        return $this === self::Active;
    }
}
