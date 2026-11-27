<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\AuditTrail\Models\AuditEvent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditEvent>
 */
class AuditEventFactory extends Factory
{
    protected $model = AuditEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $now = Carbon::now();

        return [
            'actor_type' => 'system',
            'actor_id' => 'system',
            'action' => 'test.event',
            'category' => 'test',
            'resource_type' => 'test_resource',
            'resource_id' => (string) fake()->unique()->numberBetween(1, 100000),
            'occurred_at' => $now,
            'source' => 'domain',
            'created_at' => $now,
        ];
    }
}
