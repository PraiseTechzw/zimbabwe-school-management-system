<?php

declare(strict_types=1);

namespace App\Domains\AuditTrail\Models;

use Illuminate\Database\Eloquent\Builder;
use LogicException;

/**
 * Blocks mass mutation through the Eloquent query builder.
 *
 * @extends Builder<AuditEvent>
 */
final class ImmutableAuditEventBuilder extends Builder
{
    public function update(array $values)
    {
        throw new LogicException('Audit events are append-only and cannot be updated.');
    }

    public function increment($column, $amount = 1, array $extra = [])
    {
        throw new LogicException('Audit events are append-only and cannot be incremented.');
    }

    public function decrement($column, $amount = 1, array $extra = [])
    {
        throw new LogicException('Audit events are append-only and cannot be decremented.');
    }

    public function incrementEach(array $columns, array $extra = [])
    {
        throw new LogicException('Audit events are append-only and cannot be incremented.');
    }

    public function decrementEach(array $columns, array $extra = [])
    {
        throw new LogicException('Audit events are append-only and cannot be decremented.');
    }

    public function updateOrInsert(array $attributes, array|callable $values = [])
    {
        throw new LogicException('Audit events are append-only and cannot be upserted.');
    }

    public function upsert(array $values, $uniqueBy, $update = null)
    {
        throw new LogicException('Audit events are append-only and cannot be upserted.');
    }

    public function truncate()
    {
        throw new LogicException('Audit events are append-only and cannot be truncated.');
    }

    public function delete()
    {
        throw new LogicException('Audit events are append-only and cannot be deleted.');
    }
}
