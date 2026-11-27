<?php

declare(strict_types=1);

namespace App\Domains\School\Scopes;

use App\Domains\School\Context\SchoolContext;
use App\Domains\School\Models\School;
use Illuminate\Database\Eloquent\Builder;

/**
 * Explicit scoping for school-owned records.
 *
 * Queries that use forCurrentSchool() fail closed when no school has been
 * authorised for the request. Trusted console/seed paths can use forSchool()
 * with an explicitly resolved school.
 */
trait ScopesToCurrentSchool
{
    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForSchool(Builder $query, School $school): Builder
    {
        return $query->where($this->qualifyColumn('school_id'), (int) $school->id);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForCurrentSchool(Builder $query): Builder
    {
        /** @var SchoolContext $context */
        $context = app(SchoolContext::class);

        return $query->forSchool($context->assertCurrent());
    }
}
