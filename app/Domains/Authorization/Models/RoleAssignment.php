<?php

declare(strict_types=1);

namespace App\Domains\Authorization\Models;

use App\Domains\School\Models\School;
use App\Models\User;
use Carbon\CarbonInterface;
use Database\Factories\RoleAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A configured role held by a user in one server-resolved school.
 *
 * @property int $id
 * @property int $school_id
 * @property int $user_id
 * @property string $role
 * @property string $source
 * @property CarbonInterface $created_at
 *
 * @method static Builder<RoleAssignment> forSchool(School|int $school)
 * @method static Builder<RoleAssignment> forUser(User|int $user)
 */
#[Guarded(['*'])]
class RoleAssignment extends Model
{
    /** @use HasFactory<RoleAssignmentFactory> */
    use HasFactory;

    protected $table = 'role_assignments';

    public $timestamps = false;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'immutable_datetime',
    ];

    protected static function newFactory(): Factory
    {
        return RoleAssignmentFactory::new();
    }

    /**
     * @param  Builder<RoleAssignment>  $query
     */
    public function scopeForSchool(Builder $query, School|int $school): void
    {
        $query->where('school_id', $school instanceof School ? (int) $school->id : $school);
    }

    /**
     * @param  Builder<RoleAssignment>  $query
     */
    public function scopeForUser(Builder $query, User|int $user): void
    {
        $query->where('user_id', $user instanceof User ? (int) $user->id : $user);
    }

    /**
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
