<?php

declare(strict_types=1);

namespace App\Domains\School\Models;

use App\Models\User;
use Carbon\Carbon;
use Database\Factories\SchoolFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'type', 'ward', 'district', 'province'])]
class School extends Model
{
    /** @use HasFactory<SchoolFactory> */
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return SchoolFactory::new();
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function identifiers(): HasMany
    {
        return $this->hasMany(SchoolIdentifier::class);
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(SchoolStatus::class);
    }

    public function relationships(): HasMany
    {
        return $this->hasMany(SchoolRelationship::class, 'school_id');
    }

    public function relatedRelationships(): HasMany
    {
        return $this->hasMany(SchoolRelationship::class, 'related_school_id');
    }

    public function currentStatus(?Carbon $asOf = null): ?SchoolStatus
    {
        $asOf ??= Carbon::now();

        return SchoolStatus::query()
            ->where('school_id', $this->id)
            ->where('valid_from', '<=', $asOf->toDateString())
            ->where(function (Builder $query) use ($asOf): void {
                $query->whereNull('valid_to')
                    ->orWhere('valid_to', '>=', $asOf->toDateString());
            })
            ->orderByDesc('valid_from')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();
    }
}
