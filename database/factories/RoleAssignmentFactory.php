<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Authorization\Models\RoleAssignment;
use App\Domains\School\Models\School;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoleAssignment>
 */
class RoleAssignmentFactory extends Factory
{
    protected $model = RoleAssignment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'user_id' => User::factory(),
            'role' => 'school_member',
            'source' => 'domain',
            'created_at' => Carbon::now(),
        ];
    }
}
