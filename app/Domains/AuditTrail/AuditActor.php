<?php

declare(strict_types=1);

namespace App\Domains\AuditTrail;

use App\Models\User;
use InvalidArgumentException;

/**
 * Minimal actor identity for an audit event.
 *
 * Only identifiers are stored; names, email addresses and other personal
 * attributes are deliberately excluded.
 */
final readonly class AuditActor
{
    private function __construct(
        public string $type,
        public string $id,
    ) {}

    public static function user(User|int $user): self
    {
        $id = $user instanceof User ? (int) $user->getKey() : $user;

        if ($id <= 0) {
            throw new InvalidArgumentException('An audit user actor requires a positive identifier.');
        }

        return new self('user', (string) $id);
    }

    public static function system(): self
    {
        return new self('system', 'system');
    }

    public static function job(string $name): self
    {
        $name = trim($name);

        if ($name === '' || strlen($name) > 100) {
            throw new InvalidArgumentException('An audit job actor name must be 1-100 characters.');
        }

        return new self('job', $name);
    }
}
